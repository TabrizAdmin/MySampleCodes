<?php

namespace App\Models\Base;

use App\Services\SMS\SMSThrottle;
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use App\Models\Base\Point;
use App\Models\Base\Country;
use App\Models\Base\City;

class User extends Authenticatable implements MustVerifyEmail, SMSThrottle
{
    use Notifiable;
    use SoftDeletes;
    use HasApiTokens;

    protected $table = 'new_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'email', 'password', 'api_token', 'first_name', 'last_name', 'phone', 'current_city_id',
        'country_id', 'date_of_birth', 'gender', 'verification_code', 'verified', 'last_login',
        'invited_by_id', 'who_can_message', 'who_can_see', 'who_can_see_past', 'who_can_post', 'who_want_see', 'who_want_see_place_id', 'is_admin'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'verified' => 'boolean',
        'is_admin' => 'boolean'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'email_verified_at', 'last_login', 'deleted_at', 'last_sms', 'last_email'
    ];

    static public function register($requestData)
    {
        $token = str_random(60);
        $requestData['api_token'] = hash('sha256', $token);
        $requestData['password'] = bcrypt($requestData['password']);
        return self::create($requestData);
    }

    static function emailExists($email)
    {
        $email_exists = false;
        $user = self::where('email', $email)->first();
        if (!is_null($user)) {
            $email_exists = true;
        }

        return $email_exists;
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Base\Country');
    }

    public function currentCity()
    {
        return $this->belongsTo('App\Models\Base\City', 'current_city_id');
    }

    public function invitedBy()
    {
        return $this->belongsTo('App\Models\Base\User', 'invited_by_id');
    }

    public function subsets()
    {
        return $this->hasMany('App\Models\Base\User', 'invited_by_id');
    }

    public function citiesOfExpertise()
    {
        return $this->belongsToMany('App\Models\Base\City', 'new_cities_expertise', 'user_id', 'city_id')->withTimestamps();
    }

    public function countriesTraveled()
    {
        return $this->belongsToMany('App\Models\Base\Country', 'new_traveled_countries', 'user_id', 'country_id')->withTimestamps();
    }

    public function friends()
    {
        return $this->belongsToMany('App\Models\Base\User', 'new_friends', 'user_id', 'friend_id')->withTimestamps()->withPivot('deleted_at');
    }

    public function friendsNotDeleted()
    {
        return $this->belongsToMany('App\Models\Base\User', 'new_friends', 'user_id', 'friend_id')->withTimestamps()->wherePivot('deleted_at', null);
    }

    public function friendRequests()
    {
        return $this->belongsToMany('App\Models\Base\User', 'new_friend_requests', 'user_id', 'friend_id')->withTimestamps()->withPivot('deleted_at');
    }

    public function friendRequestsRecieved()
    {
        return $this->belongsToMany('App\Models\Base\User', 'new_friend_requests', 'friend_id', 'user_id')->withTimestamps()->withPivot('deleted_at');
    }

    public function points()
    {
        return $this->hasMany('App\Models\Base\Point');
    }

    public function addPoints($count, $description, $cityId = null, $createdById = null)
    {
        $point = new Point;
        $point->user_id = $this->id;
        $point->description = $description;
        $point->count = $count;
        if ($cityId != null)
            $point->city_id = $cityId;
        if ($createdById != null)
            $point->created_by = $createdById;
        $point->save();
    }

    public function reportedBugs()
    {
        return $this->hasMany('App\Models\Base\Bug');
    }

    public function posts()
    {
        return $this->hasMany('App\Models\Base\Post');
    }

    public function notifications()
    {
        return $this->hasMany('App\Models\Base\Notification', 'user_id', 'id');
    }

    public function activities()
    {
        return $this->hasMany('App\Models\Activity\Activity', 'created_by');
    }

    public function ratings()
    {
        return $this->hasMany('App\Models\Activity\Rating');
    }

    public function ratingVotes()
    {
        return $this->belongsToMany('App\Models\Activity\Rating', 'new_activity_rating_votes', 'user_id', 'activity_rating_id')->withPivot('upvote')->withTimestamps();
    }

    public function trips()
    {
        return $this->hasMany('App\Models\Trip\Trip');
    }

    public function tripsAsTraveller()
    {
        return $this->belongsToMany('App\Models\Trip\Trip', 'new_trip_travellers', 'traveller_id', 'trip_id')->withTimestamps();
    }

    public function tripRequests()
    {
        return $this->belongsToMany('App\Models\Base\User', 'new_trip_requests', 'user_id', 'friend_id')->withTimestamps();
    }

    public function tripInvites()
    {
        return $this->belongsToMany('App\Models\Base\User', 'new_trip_requests', 'friend_id', 'user_id')->withTimestamps();
    }

    public function tripFriendInvites()
    {
        return $this->hasMany('App\Models\Trip\FriendRequest');
    }

    public function helpMessages()
    {
        return $this->hasMany('App\Models\Trip\DestinationHelp', 'sender_id');
    }

    public function recommendations()
    {
        return $this->hasMany('App\Models\Activity\Recommend');
    }

    public function likes()
    {
        return $this->hasMany('App\Models\Polymorph\social')->where('type', 'like');
    }

    public function bookmarks()
    {
        return $this->hasMany('App\Models\Polymorph\social')->where('type', 'bookmark');
    }

    public function shares()
    {
        return $this->hasMany('App\Models\Polymorph\social')->where('type', 'share');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Models\Chat\Group', 'new_chat_group_participants', 'participant_id', 'group_id')->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany('App\Models\Chat\Message', 'sender_id');
    }

    public function media()
    {
        return $this->morphMany('App\Models\Polymorph\Attachment', 'attachable');
    }

    public function avatars()
    {
        return $this->morphMany('App\Models\Polymorph\Attachment', 'attachable')->where('user_media_type', 'avatar');
    }

    public function notifiables()
    {
        return $this->morphMany('App\Models\Base\Notification', 'notifiable');
    }

    public function reports()
    {
        return $this->morphMany('App\Models\Polymorph\Report', 'reportable');
    }

    public function groupName()
    {
        return sprintf('%s-changes-channel', $this->username);
    }

    public function requestedVerificationSMS($code, $phone)
    {
        $this->verification_code = $code;
        $this->phone = $phone;
        $this->verified = false;
        $this->last_sms = Carbon::now();
        $this->save();
    }

    public function shouldThrottle(): bool
    {
        $now = Carbon::now();
        if ($this->last_sms != null) {
            $two_mins = Carbon::parse($this->last_sms)->addMinutes(2);
            if ($two_mins->greaterThan($now)) {
                return true;
            }
        }

        return false;
    }

    public function throttleEmail(): bool
    {
        $now = Carbon::now();
        if ($this->last_email != null) {
            $two_mins = Carbon::parse($this->last_email)->addMinutes(2);
            if ($two_mins->greaterThan($now)) {
                return true;
            }
        }

        return false;
    }

    public function makeVerify()
    {
        $this->verified = true;
        if ($this->last_email != null) {
            $this->email_verified_at = Carbon::now();
        }
        $this->addPoints(5, "Complete profile and verify point");
        $this->save();
    }

    public function emailSent($code)
    {
        $this->last_email = Carbon::now();
        $this->verification_code = $code;
        $this->save();
    }

    public function updatePassword($password)
    {
        $this->password = bcrypt($password);
        $this->save();
    }

    public function updateCurrentCity($data)
    {
        list($placeId, $cityName, $imageUrl, $coordinates, $countryName, $countryShortName) = $data;

        $city = City::getCity($placeId, $cityName, $coordinates, $countryName, $imageUrl);
        $cityId = $city->id;

        $this->update(['current_city_id' => $cityId]);
    }

    public function updateCitiesOfExpertise($citiesExpertise)
    {
        $cities_of_expertise = array();
        foreach (json_decode($citiesExpertise) as $city_array) {
            $city = City::getCity($city_array->place_id, $city_array->city_name, $city_array->lat_lng, $city_array->country_long_name, $city_array->photo_reference);
            array_push($cities_of_expertise, $city->id);
        }
        if ($citiesExpertise != null)
            $this->citiesOfExpertise()->sync($cities_of_expertise);
    }
}
