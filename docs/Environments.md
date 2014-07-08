Hyperion Client Environments
============================
In Hyperion, there are 3 types of environments:

* Bakery
* Test
* Production

The bakery is an isolated environment just for the purpose of spinning up a new instance and configuring it. It will
be shutdown immediately and is the only environment that doesn't have an environment name.

The test environment is the only environment that allows multiple distributions, however each stack may only have a
single instance. The test environment should be used for CI builds, UAT, SIT, etc environments. When a new test build
is complete, all previous builds with the same name are torn-down (thus only one distribution per 'name' should exist).

The test environment may have multiple distributions based on different names, this would typically be derived from
branch names.

The production environment is home to all production and staging environments. These permit only a single distribution
per environment and allow full scaling, etc.

Multiple Environments
---------------------
It is possible to have multiple of any environment type, allowing you to have separate prod/staging environments, or
even the ability to have different CI/UAT or as far as different build scripts for the bakery.

However, despite being allowed multiple bakery environments, a project only ever shares one base image. The last bake
will be the template for all new production, staging, CI, etc environments.
