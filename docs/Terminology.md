Terminology
===========

General Terms
-------------
### Distribution
A set of instances that comprises a build or release, synonymous with the industry term "stack".

### Bake
The process of creating an instance from a fresh template, configuring it to match the webserver and saving an image
of the instance.

### Build
Spinning up a new instance for a test environment, this normally results in the termination of the previous build.

### Deploy
The process of building all production (or stage) instances, swapping load balancers and terminating the previous
deployment.

### Release
See 'Deploy' - although 'Release' is normally the consumer facing lingo.


Workflow Terms
--------------
### Action
A bake, deploy, scale or tear-down.

### Command
A process performed while running an action, this is normally an API call to a cloud provider.

### Command Driver
The low-level implementation for a specific command.

### Task
A decision of action task, one of the events in the workflow.

### Workflow
The series of events required to complete an action.


