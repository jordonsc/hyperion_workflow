Event Triggers
==============
When an action completes (success or fail) a call is made to the API server with the following details:

* Project ID
* Environment ID
* Action ID
* Command (eg bake, build, release)
* Success (success/fail)
* Build name
* Details (eg output)

Any given parameter may be null, depending on the action.

Notifications
-------------
A project may have notification endpoints assigned. Upon a trigger being hit, all notification end-points should be
called.

### Endpoints

AWS SNS would be ideal, but it's impossible for 3rd parties to subscribe without a AWS confirmation.

Consider:

 * SNS (requires AWS account)
 * Email (text)
 * Email (JSON)
 * HTTP hit (JSON payload)

If using email/HTTP - we might need a callback to confirm the validity of the message.

Reactions
---------
A trigger may fire new actions:

* Tear-down old builds
* Start a release process

