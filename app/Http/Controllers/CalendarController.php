<?php

namespace App\Http\Controllers;

use App\Events;
use App\Services\EventService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Illuminate\Http\Request;

class CalendarController extends Controller
{

    protected $access_token, $googleCalendarService, $eventService;

    /**
     * CalendarController constructor.
     * @param GoogleCalendarService $googleCalendarService
     * @param EventService $eventService
     */
    public function __construct(GoogleCalendarService $googleCalendarService, EventService $eventService)
    {
        $this->googleCalendarService = $googleCalendarService;
        $this->eventService = $eventService;
        $this->access_token = $this->getAccessToken();
        if (is_null($this->access_token)) {
            $result['status'] = 404;
            $result['message'] = "Authentication fail";
            return $result;
        }
    }


    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return $this->googleCalendarService->getAllEvents($this->access_token);
    }

    /**
     * Stores events to google calender
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $calendarData = $request->all();
            $calendar_service_result = $this->googleCalendarService->store($this->access_token, $calendarData);
            if ($calendar_service_result['status'] != 200) {
                $result = $calendar_service_result;
                return response()->json($result);
            } else {
                $this->eventService->create($calendar_service_result['data']);
            }
            $result['status'] = 200;
            $result['data'] = $calendar_service_result['data'];
            $result['message'] = 'Event created successfully';
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
            $result = $this->googleCalendarService->show($this->access_token, $id);
            return response()->json($result);
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
            $data = $request->all();
            $calendar_service_result = $this->googleCalendarService->update($this->access_token, $id, $data);
            if ($calendar_service_result['status'] != 200) {
                $result = $calendar_service_result;
                return response()->json($result);
            } else {
                $this->eventService->update($id, $calendar_service_result['data']);
            }
            $result['status'] = 200;
            $result['data'] = $calendar_service_result['data'];
            $result['message'] = 'Event updated successfully';
            return response()->json($result);
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
            $calendar_service_result = $this->googleCalendarService->destroy($this->access_token, $id);
            if ($calendar_service_result['status'] != 200) {
                $result = $calendar_service_result;
                return response()->json($result);
            } else {
                $this->eventService->destroy($id);
            }
            $result['status'] = 200;
            $result['message'] = 'Event deleted successfully';
            return response()->json($result);
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

}
