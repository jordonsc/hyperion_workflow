Hyperion Data Abstraction
=========================
From a component point of view, there is no database to talk to. All Hyperion components will converse with entities
via the API server. The API server has a CRUD API to communicate with the database.

The Hyperion DBAL repository acts as both a DBAL between any given component and the database, as well as an API client
for further API functions outside of the CRUD interface.
