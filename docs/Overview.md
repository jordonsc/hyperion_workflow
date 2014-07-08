Hyperion Overview
=================

The Hyperion project is a workflow, broken into lots of small parts. The
[Hyperion Workflow diagram](https://www.lucidchart.com/documents/edit/5a1a820b-7293-4fb3-b670-f9c9b4ab6e00/0) explains
the process and components. Because the components are loosely coupled and easily scalable, this allows the Hyperion
application to scale vastly without any concerns. It's technically possible for Hyperion to redeploy itself while
keeping track of it's own deployment state.

Project Standards
-----------------
The Hyperion project follows the [Bravo 3 standards](https://github.com/bravo3/standards/blob/master/README.md).

All non-critical components of Hyperion are released as open source via the Bravo 3 group on Github under the Bravo 3
namespace. Components not released (propriety) are:

* Hyperion Workflow
* Hyperion API Server
* Hyperion DBAL (which will be moved to open-source when complete, although not under the Bravo 3 name)

Core components that are open source under the Bravo 3 name are:

* SSH - PHP SSH suite
* Bakery - remote baking process via SSH
* Cloud Controller - PHP cloud API abstraction

API Server
----------
The API server talks to a PDO database (MySQL at current) to store all database information
(see [Data Abstraction](DataAbstraction.md) for details) and act as a mediator for all communication. All events start
from a call to the API server.

DBAL
----
The DBAL is a PHP library that is included in nearly every component. It acts as an API client for each component as
well as providing data connectivity. The DBAL also contains the "standard" for all data structures that each component
may reference. The DBAL also includes a command line executable allowing the DBAL to be used as a client command
console, an SDK and a PHP library.

API Management Layer
--------------------
There is none. The API is completely exposed.

Workflow
--------
The workflow is where the magic is. This is powered using Amazon SWF (simple workflow) and is broken into 2 main
components: the decider and the worker.

When a workflow is started or a task completes, SWF will ask the decider to make a decision. Based on the state of the
workflow, the decider will schedule some tasks and respond to SWF. For each scheduled task, SWF will ask the worker to
action them. As such, it's ideal to have many worker threads running, and only a few decider threads.

While SWF allows for a complex workflow containing many worker types, Hyperion keeps it simple by only having 1 worker
type - a command worker. A 'command' is the directive to each worker, the worker loads the appropriate command driver
and executes the task. When complete, SWF will then return to the decider for next steps.

Typical commands are things like starting instances, checking instance states, assigning resources, etc.

Workflow State
--------------
A workflow has a state by multiple means, the SWF history and the action state. The SWF history is used to detect
failures, if the decider sees a failed task in the history, it will tell SWF to terminate the workflow (and fail the
action). The history also includes the initial workflow input, which is used to link to the action ID containing
the procedure the workflow is trying to achieve.

The actual state is more ephemeral, the decider and worker threads have access to a memory cache (currently Redis)
which holds all information critical to the workflow. For example, if baking an instance, the state of the instance
is stored in the memory cache so that the decider knows what to do next.
