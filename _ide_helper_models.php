<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $trade_name
 * @property string $legal_name
 * @property string $cnpj
 * @property string $email
 * @property string $password
 * @property string $phone
 * @property string|null $address_street
 * @property string|null $address_number
 * @property string|null $district
 * @property string $city
 * @property string $state
 * @property \App\Enums\UserType $type
 * @property int $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Space> $spaces
 * @property-read int|null $spaces_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StorageRequest> $storageRequests
 * @property-read int|null $storage_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereAddressNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereAddressStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCnpj($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereDistrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereTradeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company withoutTrashed()
 */
	class Company extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $storage_request_id
 * @property int $space_id
 * @property numeric|null $price
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property \App\Enums\QuoteStatus $status
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Space $space
 * @property-read \App\Models\StorageRequest $storageRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereSpaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereStorageRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote whereValidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quote withoutTrashed()
 */
	class Quote extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string $zip_code
 * @property string $address
 * @property string $number
 * @property string $district
 * @property string $city
 * @property string $state
 * @property int $temp_min
 * @property int $temp_max
 * @property int $capacity
 * @property string $capacity_unit
 * @property \App\Enums\SpaceType $type
 * @property bool $has_anvisa
 * @property bool $has_security
 * @property bool $has_generator
 * @property bool $has_dock
 * @property string|null $operating_hours
 * @property bool $allows_extended_hours
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SpacePhoto> $photos
 * @property-read int|null $photos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereAllowsExtendedHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereCapacityUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereDistrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereHasAnvisa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereHasDock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereHasGenerator($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereHasSecurity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereOperatingHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereTempMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereTempMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Space withoutTrashed()
 */
	class Space extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Space|null $space
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpacePhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpacePhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpacePhoto query()
 */
	class SpacePhoto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property \App\Enums\ProductType $product_type
 * @property string|null $description
 * @property int $quantity
 * @property \App\Enums\UnitType $unit
 * @property int $temp_min
 * @property int $temp_max
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property \App\Enums\RequestStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $target_city
 * @property string|null $target_state
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereProductType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereTargetCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereTargetState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereTempMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereTempMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageRequest withoutTrashed()
 */
	class StorageRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

