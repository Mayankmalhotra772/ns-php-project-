<?php
/**
 * Rate limiting middleware.
 * Tracks and enforces request rate limits using atomic DB operations.
 */

function checkRateLimit(string $identifier, string $action, int $maxAttempts = RATE_LIMIT_MAX_REQUESTS, int $window = RATE_LIMIT_WINDOW): bool {
    try {
        $db = Database::getConnection();

        // Atomic upsert + check in a single query to prevent race conditions
        $stmt = $db->prepare(
            "INSERT INTO rate_limits (identifier, action, attempts, window_start)
             VALUES (:identifier, :action, 1, CURRENT_TIMESTAMP)
             ON CONFLICT (identifier, action) DO UPDATE SET
                attempts = CASE
                    WHEN rate_limits.window_start < :cutoff THEN 1
                    ELSE rate_limits.attempts + 1
                END,
                window_start = CASE
                    WHEN rate_limits.window_start < :cutoff THEN CURRENT_TIMESTAMP
                    ELSE rate_limits.window_start
                END
             RETURNING attempts"
        );
        $cutoff = date('Y-m-d H:i:s', time() - $window);
        $stmt->execute([
            ':identifier' => $identifier,
            ':action' => $action,
            ':cutoff' => $cutoff,
        ]);
        $row = $stmt->fetch();

        // Clean up old entries periodically (non-blocking)
        if (random_int(1, 100) <= 5) {
            $cleanup = $db->prepare('DELETE FROM rate_limits WHERE window_start < :cutoff');
            $cleanup->execute([':cutoff' => $cutoff]);
        }

        return ($row['attempts'] <= $maxAttempts);
    } catch (Exception $e) {
        error_log('Rate limit check failed: ' . $e->getMessage());
        return false; // Fail closed — block request on DB error
    }
}

function isAccountLocked(string $username): bool {
    try {
        $db = Database::getConnection();

        // Check user lock status
        $stmt = $db->prepare(
            'SELECT is_locked, lock_until FROM users WHERE username = :username'
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && $user['is_locked']) {
            if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
                return true;
            }
            // Lock expired, unlock
            $stmt = $db->prepare(
                'UPDATE users SET is_locked = FALSE, lock_until = NULL WHERE username = :username'
            );
            $stmt->execute([':username' => $username]);
            return false;
        }

        // Count recent failed attempts
        $stmt = $db->prepare(
            'SELECT COUNT(*) as cnt FROM failed_logins
             WHERE username = :username AND attempted_at > :cutoff'
        );
        $stmt->execute([
            ':username' => $username,
            ':cutoff'   => date('Y-m-d H:i:s', time() - LOCKOUT_DURATION),
        ]);
        $result = $stmt->fetch();

        if ($result['cnt'] >= MAX_LOGIN_ATTEMPTS) {
            // Lock the account
            $stmt = $db->prepare(
                'UPDATE users SET is_locked = TRUE, lock_until = :lock_until WHERE username = :username'
            );
            $stmt->execute([
                ':username'   => $username,
                ':lock_until' => date('Y-m-d H:i:s', time() + LOCKOUT_DURATION),
            ]);
            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log('Account lock check failed: ' . $e->getMessage());
        return false;
    }
}
