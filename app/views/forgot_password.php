<?php ob_start(); ?>

<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-indigo-700">Forgot Password</h1>
            <p class="text-gray-500 mt-1">Enter your registered email address</p>
        </div>

        <form method="POST" action="/forgot-password" autocomplete="off">
            <?= $csrfField ?>

            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required maxlength="255"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                Send Reset Link
            </button>
        </form>

        <?php if (!empty($_SESSION['flash_reset_link'])): ?>
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm">
                <p class="font-medium text-blue-800 mb-2">Your password reset link:</p>
                <a href="<?= htmlspecialchars($_SESSION['flash_reset_link']) ?>"
                   class="text-blue-600 break-all hover:underline">
                    <?= htmlspecialchars($_SESSION['flash_reset_link']) ?>
                </a>
                <p class="text-blue-600 mt-2 text-xs">This link expires in 1 hour and can only be used once.</p>
            </div>
            <?php unset($_SESSION['flash_reset_link']); ?>
        <?php endif; ?>

        <p class="text-center text-sm text-gray-500 mt-4">
            Remembered it? <a href="/login" class="text-indigo-600 hover:underline">Back to Login</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Forgot Password', $content, false);
