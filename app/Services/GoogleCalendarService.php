<?php
/**
 * Created by PhpStorm.
 * User: Saurav KC
 * Date: 7/27/2018
 * Time: 7:46 PM
 */
namespace App\Services;

use App\Events;
use Exception;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;



Class GoogleCalendarService extends Service
{

    protected $client;


    /**
     * GoogleCalendarService constructor.
     * @throws \Google_Exception
     */
    public function __construct()
    {
        $client = new Google_Client();
        $client->setAuthConfig('client_secret.json');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false)));
        $client->setHttpClient($guzzleClient);
        $this->client = $client;
    }

    /**
     * @param $access_token
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllEvents($access_token)
    {
        try {
            $this->client->setAccessToken($access_token);
            $service = new Google_Service_Calendar($this->client);
            $calendarId = env('GOOGLE_CALENDAR_ID');
            $events = $service->events->listEvents($calendarId);
            $result['status'] = 200;
            $result['message'] = 'Events fetched successfully';
            $result['events'] = $events->getItems();
            return response()->json($result);
        } catch (\Exception $e) {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return response()->json($result);
        }
    }


    /**
     * @param $access_token
     * @param array $eventData
     * @return mixed
     */
    public function store($access_token, array $eventData)
    {
        try {
            $start_date_time = $eventData['start_date'];
            $end_date_time = $eventData['end_date'];
            $timezone = $eventData['timezone'];
            $this->client->setAccessToken($access_token);
            $service = new Google_Service_Calendar($this->client);
            $calendarId = env('GOOGLE_CALENDAR_ID');
            $event = new Google_Service_Calendar_Event([
                'summary' => $eventData['summary'],
                'description' => $eventData['description'],
                'start' => ['dateTime' => $start_date_time, 'timeZone' => $timezone],
                'end' => ['dateTime' => $end_date_time, 'timeZone' => $timezone],
                'reminders' => ['useDefault' => true],
            ]);
            if (isset($eventData['recurrent_freq']) && $eventData['recurrent_freq'] != 'none') {
                $freq = strtoupper($eventData['recurrent_freq']);
                $count = $eventData['recurrent_count'] ?: 0;
                $event->setRecurrence(array('RRULE:FREQ=' . $freq . ';COUNT=' . $count . ';'));
            }
            $calendar_result = $service->events->insert($calendarId, $event);
            $result['status'] = 200;
            $result['message'] = 'Calendar data saved';
            $result['data'] = $calendar_result;
            return $result;

        }
        catch (\Exception $e)
        {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return $result;
        }

    }

    /**
     * @param $access_token
     * @param $id
     * @return mixed
     */
    public function show($access_token, $id)
    {
        $this->client->setAccessToken($access_token);
        $service = new Google_Service_Calendar($this->client);
        $event = $service->events->get('primary', $id);
        if (!$event) {
            $result['status'] = 500;
            $result['message'] = "Something went wrong";
        }
        $result['status'] = 200;
        $result['message'] = "Event fetched successfully";
        $result['data'] = $event;
        return $result;
    }

    /**
     * @param $access_token
     * @param $id
     * @param array $eventData
     * @return mixed
     */
    public function update($access_token, $id, array $eventData)
    {
        try {
            $this->client->setAccessToken($access_token);
            $service = new Google_Service_Calendar($this->client);
            $startDateTime = Carbon::parse($eventData['start_date'])->toRfc3339String();
            $endDateTime = Carbon::parse($eventData['end_date'])->toRfc3339String();
            $timezone = $eventData['timezone'];
            $event = $service->events->get('primary', $id);
            $event->setSummary($eventData['summary']);
            $event->setDescription($eventData['description']);
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($startDateTime);
            $start->setTimeZone($timezone);
            $event->setStart($start);
            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($endDateTime);
            $end->setTimeZone($timezone);
            $event->setEnd($end);
            $updatedEvent = $service->events->update('primary', $event->getId(), $event);
            $result['status'] = 200;
            $result['message'] = "Calendar updated successfully";
            $result['data'] = $updatedEvent;
            return $result;
        }
        catch (\Exception $e)
        {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return $result;
        }

    }

    public function destroy($access_token, $id)
    {
        try {
            $this->client->setAccessToken($access_token);
            $service = new Google_Service_Calendar($this->client);
            $service->events->delete('primary', $id);
            $result['status'] = 200;
            $result['message'] = "Event deleted successfully";
            return $result;
        }
        catch (\Exception $e)
        {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return $result;
        }
    }





}
