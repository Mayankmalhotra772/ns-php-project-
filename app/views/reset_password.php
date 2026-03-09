<?php ob_start(); ?>

<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-md p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-indigo-700">Reset Password</h1>
            <p class="text-gray-500 mt-1">Enter your new password below</p>
        </div>

        <form method="POST" action="/reset-password" autocomplete="off">
            <?= $csrfField ?>
            <input type="hidden" name="token" value="<?= sanitizeOutput($token) ?>">

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                <p class="text-xs text-gray-400 mt-1">Min 10 characters, with uppercase, lowercase, number and special character.</p>
            </div>

            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                Reset Password
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-4">
            <a href="/login" class="text-indigo-600 hover:underline">Back to Login</a>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Reset Password', $content, false);
