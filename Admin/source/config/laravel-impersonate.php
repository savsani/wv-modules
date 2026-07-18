<?php

return [

    /**
     * The session key used to store the original user id.
     */
    'session_key' => 'impersonator_id',

    /**
     * The session key used to stored the original user guard.
     */
    'session_guard' => 'impersonator_guard',

    /**
     * The session key used to stored what guard is impersonator using.
     */
    'session_guard_using' => 'impersonator_guard_using',

    /**
     * The default impersonator guard used.
     */
    'default_impersonator_guard' => 'web',

    /**
     * The URI to redirect after taking an impersonation.
     *
     * Unused: Modules\Admin\Http\Controllers\Users\ImpersonateController handles
     * redirects directly instead of the package's built-in controller.
     */
    'take_redirect_to' => '/',

    /**
     * The URI to redirect after leaving an impersonation.
     *
     * Unused: Modules\Admin\Http\Controllers\Users\ImpersonateController handles
     * redirects directly instead of the package's built-in controller.
     */
    'leave_redirect_to' => '/',

];
