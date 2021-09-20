<?php

namespace Laradra;

use Illuminate\Database\Eloquent\Builder;
use Laradra\Models\HydraOauth2AuthenticationSession;

/**
 * See https://openid.net/specs/openid-connect-session-1_0.html
 */
class SessionManager
{
    /**
     * @var string|null
     */
    private $connection;

    public function __construct(?string $connection = null)
    {
        $this->connection = $connection;
    }

    public function newRevokeLoginSessionQuery(string $start, string $end, ?string $subject = null)
    {
        $query = HydraOauth2AuthenticationSession::on($this->connection);

        /** @see HydraOauth2AuthenticationSession::scopeBetweenWithSubject() */
        /** @see HydraOauth2AuthenticationSession::scopeBetween() */
        /** @var Builder $query */
        return $subject
            ? $query->betweenWithSubject($start, $end, $subject)
            : $query->between($start, $end);
    }
}
