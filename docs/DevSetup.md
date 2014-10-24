Dev Environment Setup
=====================
All Hyperion applications are platform agnostic. There is no (and never will be) Vagrant Box or VM for any Hyperion
project. The only requirements are to install the Hyperion API as a vhost in your environment.

You will need to clone the API, Workflow and DBAL projects to get the full application running. You may also want to
clone some Bravo 3 projects: ssh, cloud-controller and bakery.

Server Requirements
-------------------
* PHP 5.5
* MySQL
* Redis
* Some kind of web server (Apache is good)

Ubuntu: `apt-get install -y mysql-server redis-server apache2 php5`

Virtual Host Setup
------------------
### Hyperion API Repo

A sample vhost file for Apache is available in the docs folder of the API repo. It's recommended to make a fake hosts
entry too:

`echo "127.0.0.1 api.hyperion.dev" >> /etc/hosts`

Once done, you need to install the database schema for the API:

`app/console doctrine:database:create; app/console doctrine:schema:update --force`

Then you're set. By default the DBAL will talk to the api on `api.hyperion.dev` - if you've changed this, you need to
update the DBAL config.

Workflow Setup
--------------
### Hyperion Workflow Repo

To test the workflow you need to run the decider and worker in daemon mode:

`./hyperiond daemon decider -l /tmp/decider.log`
`./hyperiond daemon worker -l /tmp/worker.log`

You can then tail the log files to keep an eye on what is happening. It's perfectly fine to make them the same file.

Testing the DBAL
----------------
### Hyperion DBAL Repo

The DBAL has a command line to play with:

`./hyperion`

Will spit out your options. From there you can create database records or fire off workflow actions. Keep in mind the
DBAL is talking to the API server, which then starts SWF workflows - so a DBAL without an API server running is
useless.
