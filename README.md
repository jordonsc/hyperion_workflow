Hyperion Documentation
======================
This repo houses the general Hyperion documentation.

* [Overview](docs/Overview.md)
* [Dev Setup](docs/DevSetup.md)
* [Data Abstraction](docs/DataAbstraction.md)
* [Client Environments](docs/Environments.md)
* [Triggers](docs/Triggers.md)
* [Terminology](docs/Terminology.md)


Diagrams
--------

* [Release Process](https://www.lucidchart.com/documents/edit/4aa78fb8-abf8-45a7-85c1-796e0c6ba1e4/0)
* [ERD](https://www.lucidchart.com/documents/edit/365ed83b-415e-486f-a4a7-3d3a9acb21d9/0)
* [Workflow](https://www.lucidchart.com/documents/edit/5a1a820b-7293-4fb3-b670-f9c9b4ab6e00/0)

Hyperion Workflow Daemons
=========================

This application enables you to do two things:

1. Make workflow decisions
2. Execute workflow actions

Either of these can be run directly from the console:

    ./hyperiond run:decision
    ./hyperiond run:worker

Which will in turn execute a single decision or worker. In production, you'll want to run these as daemons which will
continuously poll for work.

    ./hyperiond daemon decision

It's recommended to log the output, eg:

    ./hyperiond daemon decider -l /var/log/hyperion.log
    ./hyperiond daemon worker -l /var/log/hyperion.log

or break up the log files -

    ./hyperiond daemon decider -l /var/log/hyperion/decider-access.log -L /var/log/hyperion/decider-error.log
    ./hyperiond daemon worker -l /var/log/hyperion/worker-access.log -L /var/log/hyperion/worker-error.log

For details -

    ./hyperiond help daemon


System Services
===============
Hyperion has configuration and an install script for systems supporting Upstart. If your system has Upstart capability
you can simply run:

    ./hyperiond install

This will create Upstart config files in `/etc/init`, symlink a binary in `/usr/bin/` to the current working path
and start the services. This will run a single decider and a single worker on the machine. You can modify your Upstart
scripts to run multiple workers if required.

Bugs and Limitations
====================

 * Google credentials aren't supported

