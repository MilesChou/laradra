<?php

namespace Laradra\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string id
 * @property mixed authenticated_at
 * @property string subject
 * @property bool remember
 */
class HydraOauth2AuthenticationSession extends Model
{
    protected $table = 'hydra_oauth2_authentication_session';

    protected $guarded = [];

    protected $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    public function scopeBetween(Builder $query, string $start, string $end): Builder
    {
        return $query
            ->where('authenticated_at', '>', $start)
            ->where('authenticated_at', '<', $end);
    }

    public function scopeBetweenWithSubject(Builder $query, string $start, string $end, string $subject): Builder
    {
        return $this->scopeBetween($query, $start, $end)->where('subject', '=', $subject);
    }
}
