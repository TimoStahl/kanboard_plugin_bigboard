<?php

namespace Kanboard\Plugin\Bigboard;

class UserSession extends \Kanboard\Core\User\UserSession
{
    /**
     *  is the Bigboard collapsed or expanded.
     *
     * @return boolean
     */
    public function isBigboardCollapsed()
    {
        return session_is_true('bigboardCollapsed');
    }

    /**
     * Set Bigboard display mode.
     *
     * @param  bool  $is_collapsed
     */
    public function setBigboardDisplayMode($is_collapsed)
    {
        session_set('bigboardCollapsed', true);
    }

    /**
     * Set the BigBoard search string
     *
     * @param string $search a Kanboard-style search string
     */
    public function setBigboardSearch($search) {
        session_set('bigboardSearch', $search);
    }

    /**
     * Gets the BigBoard search key
     * @return mixed|null Kanboard-style search string
     */
    public function getBigboardSearch() {
        return session_get('bigboardSearch');
    }
}
