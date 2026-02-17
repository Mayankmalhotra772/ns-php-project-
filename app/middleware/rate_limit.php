<?php
/**
 * Rate limiting middleware.
 * Tracks and enforces request rate limits.
 */

function checkRateLimit(string $identifier, string $action, int $maxAttempts = RATE_LIMIT_MAX_REQUESTS, int $window = RATE_LIMIT_WINDOW): bool {
    try {
        $db = Database::getConnection();

        // Clean up old entries
        $stmt = $db->prepare(
            'DELETE FROM rate_limits WHERE window_start < :cutoff'
        );
        $stmt->execute([':cutoff' => date('Y-m-d H:i:s', time() - $window)]);

        // Check current count
        $stmt = $db->prepare(
            'SELECT attempts, window_start FROM rate_limits
             WHERE identifier = :identifier AND action = :action'
        );
        $stmt->execute([':identifier' => $identifier, ':action' => $action]);
        $row = $stmt->fetch();

        if ($row) {
            $windowStart = strtotime($row['window_start']);
            if ((time() - $windowStart) > $window) {
                // Window expired, reset
                $stmt = $db->prepare(
                    'UPDATE rate_limits SET attempts = 1, window_start = CURRENT_TIMESTAMP
                     WHERE identifier = :identifier AND action = :action'
                );
                $stmt->execute([':identifier' => $identifier, ':action' => $action]);
                return true;
            }

            if ($row['attempts'] >= $maxAttempts) {
                return false; // Rate limited
            }

            // Increment
            $stmt = $db->prepare(
                'UPDATE rate_limits SET attempts = attempts + 1
                 WHERE identifier = :identifier AND action = :action'
            );
            $stmt->execute([':identifier' => $identifier, ':action' => $action]);
            return true;
        }

        // First attempt
        $stmt = $db->prepare(
            'INSERT INTO rate_limits (identifier, action, attempts, window_start)
             VALUES (:identifier, :action, 1, CURRENT_TIMESTAMP)
             ON CONFLICT (identifier, action) DO UPDATE SET attempts = rate_limits.attempts + 1'
        );
        $stmt->execute([':identifier' => $identifier, ':action' => $action]);
        return true;
    } catch (Exception $e) {
        error_log('Rate limit check failed: ' . $e->getMessage());
        return true; // Fail open to not block legitimate users on DB error
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
