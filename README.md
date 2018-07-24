#Installation
Clone the repo and run composer update

#Google calendar
1. Enable google calendar api reference link: https://docs.simplecalendar.io/google-api-key/
2. Create credentials for API
3. Download JSON file
4. Paste the downloaded file in public folder and rename to: client_secret.json (A copy file has been added in public folder for reference).

#Running the project
1. Run php artisan serve or access the project via virtual host
2. In home page click on Google Calendar Login
3. Copy the response parameter in POSTMAN header.

>> Required Parameters for authentication are added to header in postman
access_token:
token_type: 
expires_in:
created:

#POSTMAN link
https://www.getpostman.com/collections/3d2c9c6286ab10ada870
