<?php namespace Cds\Study\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Model;

/**
 * Model
 */
class Tariff extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_tariffs';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:255'
    ];

    public $attributeNames = [
        'name' => 'название',
        'description' => 'описание',
    ];

    public $hasMany = [
        'costs_periods' => [
            TariffCostPeriod::class
        ],
        'bills' => [
            Bill::class
        ]
    ];

    public $belongsToMany = [
        'services' => [
            Service::class,
            'table' => 'cds_study_tariff_services',
        ],
        'services_without_pp' => [
            Service::class,
            'table' => 'cds_study_tariff_services',
            'scope' => 'withoutPP'
        ],
    ];

    public function beforeDelete()
    {
        $bills = $this->bills()->where('status', true)->first();

        if (!empty($bills)) {
            throw new \ValidationException(['msg' => 'По выбранным тарифам были совершены оплаты']);
        }
    }

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    /**
     * показывать ли Промо тариф пользователю
     */
    public function isShowPromo()
    {
        if ($this->id == 1) {
            $myOrgIds = Organization::my()->orderBy('id')->lists('id');
            $myBills = Bill::my()
                ->where('object_type', Organization::class)
                ->where('tariff_id', 1)
                ->orderBy('object_id')->lists('object_id');
            $diff = array_diff($myOrgIds, $myBills);

            return count($diff) > 0 ? true : false;
        }

        return true;
    }

    // назначает услуги тарифа заданному объекту (программе или организации)
    function assignTo($object, $fromDate = null, $toDate = null) {
        if ($fromDate === null)
            $fromDate = Carbon::today();
        if ($toDate === null)
            $toDate = Carbon::maxValue();

        $period = CarbonPeriod::create($fromDate, $toDate);

        $records = $this->services->pluck('id')->sort()
            ->map( function($service_id) use ($object, $period) {
                return $object->services()->makeServiceActive([
                    'service_id' => $service_id,
                    'extendPeriod' => $period,
                    'tariff_id' => $this->id,
                    'object_type' => get_class($object),
                    'object_id' => $object->id,
                ]);
            })
        ;
        return $records;
    }

    public function setClientChoiceAttribute()
    {
        $this->where('client_choice', true)->update(['client_choice' => false]);
        $this->attributes['client_choice'] = true;
    }
}
