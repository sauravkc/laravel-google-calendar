<?php
/**
 * Created by PhpStorm.
 * User: Saurav KC
 * Date: 7/27/2018
 * Time: 7:40 PM
 */

namespace App\Services;

use App\Events;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;



Class EventService extends Service
{
    /**
     * @var Log
     */
    protected $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }

    /**
     * @return Events
     */
    public function getModel()
    {
        return new Events();
    }


    /**
     * @param array $calendarData
     * @return null
     */
    public function create($calendarData)
    {
        try {
            $eventData = $this->mapDataForEvent($calendarData);
            return $this->getModel()->create($eventData);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param $id
     * @param array $calendarData
     * @return null
     */
    public function update($id, $calendarData)
    {
        try {
            $event = $this->getModel()->where('event_id',$id)->first();
            $calendarData = $this->mapDataForEvent($calendarData);
            if($event)
            {
                $event->update($calendarData);
            }
            else {
                $event = $this->create($calendarData);
            }
            return $event;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    /**
     * @param $id
     * @return null
     */
    public function destroy($id)
    {
        try {
            $event = $this->getModel()->where('event_id',$id)->first();
            if(!is_null($event))
            {
                $event->delete();
            }

        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    /**
     * @param $json_data
     * @return array
     */
    public function mapDataForEvent($json_data)
    {
        $eventData = ['client_id' => request()->header('client_id'),
            'client_email' => $json_data->creator->email,
            'client_name' => $json_data->creator->displayName,
            'event_id' => $json_data->id,
            'kind' => $json_data->kind,
            'htmlLink' => $json_data->htmlLink,
            'summary' => $json_data->summary,
            'start_date' => $json_data->start->date,
            'start_date_time' => $json_data->start->dateTime,
            'start_time_zone' => $json_data->start->timeZone,
            'end_date' => $json_data->end->date,
            'end_date_time' => $json_data->end->dateTime,
            'end_time_zone' => $json_data->end->timeZone,
            'recurrence' => json_encode($json_data->recurrence)
        ];
        return $eventData;
    }
}
