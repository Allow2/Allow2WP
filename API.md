General API Usage:

------

First you need to set up Allow2 to know about the service and allow connections to accounts.

As the administrator or developer of the site on which you wish to integrate Allow2:

1) Go to https://developer.allow2.com/dev/services (Select "Activate Developer Controls" if not already activated).
2) Create a new service, name it according to your web site or service
	eg: "Facebook"
		"Twitter"
		etc...
		
Then, either use the Wordpress plugin directly, or build a service that connects to the Allow2 API.

------

Connecting user accounts.

The first step to implementing the Allow2 Web Service API is to provide a control to users in their profile to enable
them to connect to Allow2 and associate their account on your site with their Allow2 account.

Allow2 uses Oauth2 to achieve the pairing using the following process:

1) Provide a button to the user on their account page that starts the .. TO BE COMPLETED


------

example call to check access and log if allowed as we will be giving access and using that service if it is allowed:

{
  "access_token":"6-b5d4a29c-c4fe-475a-94df-7f999e9925e7",
  "tz":      "Australia/Brisbane",
  "activities": [
    {
      "id" : 1,
      "log": false
    }
  ]
}

Example response:

if the token is invalid, which can also happen if the parent has deleted the pairing, then the response will be:

{
  "error": "invalid access token"
}

The semantic here is that pairing token is NOT valid. In that case the correct behaviour on the client end is to forget the association with Allow2 as this account is no longer being controlled by the Allow2 platform.

