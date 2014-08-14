Amazon Web Services: Simple Workflow Controller
===============================================

Workflow Configuration
----------------------

### Workflow Types

* Standard Action
    * Name: std_action
    * Version: 1.0.0
    * Task List: action_worker
    * Execution Start To Close Timeout: 1 hour
    * Task Start To Close Timeout: 1 hour
    * Child Policy:	Terminate

### Activity Types

* Action Worker
    * name: action_worker
    * Version: 1.0.0
    * Task List: action_worker
    * Task Schedule to Close Timeout: Not Specified
    * Task Schedule to Start Timeout: 5 minutes
    * Task Start to Close Timeout: 1 hour
    * Task Heartbeat Timeout: Not Specified
