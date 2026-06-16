<?php

namespace app\commands;

use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Manage admin users.
 *
 * Usage:
 *   php yii user/create <username>   create a user, prompts for password (hidden)
 *   php yii user/password <username> set/reset a user's password
 */
class UserController extends Controller
{
    /**
     * Creates a user (or updates the password if it already exists).
     */
    public function actionCreate($username)
    {
        $user = User::findByUsername($username);
        if ($user !== null) {
            $this->stdout("User \"$username\" already exists — updating password.\n");
        } else {
            $user = new User();
            $user->username = $username;
            $user->generateAuthKey();
        }

        if (!$this->setPasswordInteractively($user)) {
            return ExitCode::DATAERR;
        }

        if ($user->save()) {
            $this->stdout("Done. User \"$username\" saved (id={$user->id}).\n");
            return ExitCode::OK;
        }

        $this->stderr("Failed to save user:\n" . print_r($user->errors, true));
        return ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * Sets/resets an existing user's password.
     */
    public function actionPassword($username)
    {
        $user = User::findByUsername($username);
        if ($user === null) {
            $this->stderr("User \"$username\" not found.\n");
            return ExitCode::DATAERR;
        }
        if (!$this->setPasswordInteractively($user)) {
            return ExitCode::DATAERR;
        }
        if ($user->save()) {
            $this->stdout("Password updated for \"$username\".\n");
            return ExitCode::OK;
        }
        $this->stderr("Failed to save:\n" . print_r($user->errors, true));
        return ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * Prompts for a password twice (hidden input) and sets it on the user.
     *
     * @param User $user
     * @return bool true on success
     */
    private function setPasswordInteractively(User $user)
    {
        // Typed in the local console only; never stored in code, chat or git.
        $password = $this->prompt('Password:', ['required' => true]);
        $confirm = $this->prompt('Confirm password:', ['required' => true]);

        if ($password !== $confirm) {
            $this->stderr("Passwords do not match.\n");
            return false;
        }
        if (strlen($password) < 8) {
            $this->stderr("Password must be at least 8 characters.\n");
            return false;
        }

        $user->setPassword($password);
        if (empty($user->auth_key)) {
            $user->generateAuthKey();
        }
        return true;
    }
}
