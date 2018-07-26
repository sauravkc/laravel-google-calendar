<?php

namespace App\Http\Controllers;

use App\Events;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    protected $client, $access_token;

    /**
     * CalendarController constructor.
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
        $this->access_token = $this->getAccessToken();
    }

    /**
     * Login client using the google account and returns the required header for API
     * Once client is login the returned response from google should be added in header for every request
     * Param for headers are access_token, token_type, expires_in, created
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function login()
    {
        try {
            $return_url = action('CalendarController@login');
            $this->client->setRedirectUri($return_url);
            if (!isset($_GET['code'])) {
                $auth_url = $this->client->createAuthUrl();
                $filtered_url = filter_var($auth_url, FILTER_SANITIZE_URL);
                return redirect($filtered_url);
            } else {
                $this->client->authenticate($_GET['code']);
                $access_token = $this->client->getAccessToken();
                $result['status'] = 500;
                $result['message'] = 'Login Successful';
                $result['access_token'] = $access_token;
                $result['client_id'] = $this->client->getClientId();
                return response()->json($result);
            }
        } catch (\Exception $e) {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return response()->json($result);
        }

    }

    /**
     * Gets all the events for authenticated client.
     * Client authentication using the access_token from request header.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            if ($this->access_token) {
                $this->client->setAccessToken($this->access_token);
                $service = new Google_Service_Calendar($this->client);
                $calendarId = env('GOOGLE_CALENDAR_ID');
                $events = $service->events->listEvents($calendarId);
                $result['status'] = 200;
                $result['message'] = 'Events fetched successfully';
                $result['events'] = $events->getItems();
            } else {
                $result['status'] = 404;
                $result['message'] = "Invalid credential";
            }
            return response()->json($result);
        } catch (\Exception $e) {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return response()->json($result);
        }
    }

    /**
     * Stores events to google calender
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $start_date_time = $request->start_date;
            $end_date_time = $request->end_date;
            $timezone = $request->timezone;

            if ($this->access_token) {
                $this->client->setAccessToken($this->access_token);
                $service = new Google_Service_Calendar($this->client);
                $calendarId = env('GOOGLE_CALENDAR_ID');
                $event = new Google_Service_Calendar_Event([
                    'summary' => $request->summary,
                    'description' => $request->description,
                    'start' => ['dateTime' => $start_date_time, 'timeZone' => $timezone],
                    'end' => ['dateTime' => $end_date_time, 'timeZone' => $timezone],
                    'reminders' => ['useDefault' => true],
                ]);


                if (isset($request->recurrent_freq) && $request->recurrent_freq != 'none') {
                    $freq = strtoupper($request->recurrent_freq);
                    $count = $request->recurrent_count ?: 0;
                    $event->setRecurrence(array('RRULE:FREQ=' . $freq . ';COUNT=' . $count . ';'));
                }
                $caledar_result = $service->events->insert($calendarId, $event);

                if (!$caledar_result) {
                    $result['status'] = 500;
                    $result['message'] = 'Something went wrong';
                    return $result;
                } else {
                    $eventData = $this->mapDataForEvent($caledar_result);
                    Events::create($eventData);
                    $result['status'] = 200;
                    $result['data'] = $caledar_result;
                    $result['message'] = 'Event created successfully';
                }
            } else {
                $result['status'] = 404;
                $result['message'] = "Invalid credential";
            }
            return response()->json($result);

        } catch (\Exception $e) {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return response()->json($result);
        }
    }

    /**
     * Show event by id
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            if ($this->access_token) {
                $this->client->setAccessToken($this->access_token);

                $service = new Google_Service_Calendar($this->client);
                $event = $service->events->get('primary', $id);
                if (!$event) {
                    $result['status'] = 500;
                    $result['message'] = "Something went wrongl";
                }
                $result['status'] = 200;
                $result['message'] = "Event fetched successfully";
                $result['data'] = $event;
            } else {
                $result['status'] = 404;
                $result['message'] = "Invalid credential";
            }
            return $result;
        } catch (\Exception $e) {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return response()->json($result);
        }

    }

    /**
     * Update events using PUT or PATCH request
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            if ($this->access_token) {
                $this->client->setAccessToken($this->access_token);
                $service = new Google_Service_Calendar($this->client);

                $startDateTime = Carbon::parse($request->start_date)->toRfc3339String();
                $endDateTime = Carbon::parse($request->end_date)->toRfc3339String();
                $timezone = $request->timezone;


                $event = $service->events->get('primary', $id);

                $event->setSummary($request->summary);

                $event->setDescription($request->description);

                $start = new Google_Service_Calendar_EventDateTime();
                $start->setDateTime($startDateTime);
                $start->setTimeZone($timezone);
                $event->setStart($start);
                $end = new Google_Service_Calendar_EventDateTime();
                $end->setDateTime($endDateTime);
                $end->setTimeZone($timezone);
                $event->setEnd($end);

                $updatedEvent = $service->events->update('primary', $event->getId(), $event);


                if (!$updatedEvent) {
                    $result['status'] = 500;
                    $result['message'] = "Something went wrong";
                }
                $eventData = $this->mapDataForEvent($updatedEvent);
                $event = Events::where('event_id', $eventData['event_id'])->first();
                if ($event) {
                    $event->update($eventData);
                }
                $result['status'] = 200;
                $result['message'] = "Event updated successfully";
                $result['data'] = $updatedEvent;
            } else {
                $result['status'] = 404;
                $result['message'] = "Invalid credential";
            }
            return $result;
        } catch (\Exception $e) {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return response()->json($result);
        }
    }

    /**
     * Deletes the event
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            if ($this->access_token) {
                $this->client->setAccessToken($this->access_token);
                $service = new Google_Service_Calendar($this->client);
                $service->events->delete('primary', $id);

                $event = Events::where('event_id', $id)->first();
                if ($event) {
                    $event->delete();
                }

                $result['status'] = 200;
                $result['message'] = "Event deleted successfully";
            } else {
                $result['status'] = 404;
                $result['message'] = "Invalid credential";
            }
            return $result;
        } catch (\Exception $e) {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return response()->json($result);
        }

    }


    /**
     * Gets the access token of client for authentication
     * @return array|null
     */
    public function getAccessToken()
    {
        $access_token = null;
        if (request()->header('access-token') &&
            request()->header('token-type') &&
            request()->header('expires-in') &&
            request()->header('created') &&
            request()->header('client_id')) {
            $access_token = ["access_token" => request()->header('access-token'),
                "token_type" => request()->header('token-type'),
                "expires_in" => request()->header('expires-in'),
                "created" => request()->header('created'),
                "client_id" => request()->header('client_id')];
        }
        return $access_token;
    }

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
