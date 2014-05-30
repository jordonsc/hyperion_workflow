Aborting Workflow Daemons
=========================

If your operating system supports `pcntl_signal`, you can use CTRL+C to gracefully abort the workflow daemon
without breaking a polling cycle. This will ensure no lingering workflows remain.

However, by default PHP will forbid the use of the `pcntl_signal` and `pcntl_signal_dispatch` functions. To enable
this feature you will need to remove those two functions from the php.ini directive `disable_functions`.
