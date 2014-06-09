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


Bugs and Limitations
--------------------

 * Google credentials aren't supported

