# WSUWP VALS Custom Roles

[![Build Status](https://travis-ci.org/washingtonstateuniversity/WSUWP-Plugin-VALS-Roles.svg?branch=master)](https://travis-ci.org/washingtonstateuniversity/WSUWP-Plugin-VALS-Roles)

A WordPress plugin that provides custom roles for the VALS Program site

## Provided Roles

* **VALS Registered Trainee** - Users with this role have `read` capabilities and can view only their profile (any other admin pages redirect). They can be assigned to a VALS Center.
* **VALS Certified** - Users with this role have the same capabilities and access levels as VALS Registered Trainees. They can be assigned a Certification Date and to a VALS Center.
* **VALS Center Admin** - Users with this role have `read`, `list_users`, and `promote_users` capabilities. They can be assigned to a VALS Center, and when viewing the Users list, they can see only users assigned to the same VALS Center and one of the provided roles. They can only promote users to one of the other two provided roles.

## Other Features

A stylesheet that hides a majority of the default interface is enqueued for profile pages belonging to users with any of the provided roles. The *First Name*, *Last Name*, and *Account Management* fields are left available.

The plugin also adds *Date Certified* and *Center* columns to the Users list. In both the *Date Certified* column and in *VALS Certified* users profiles, the certification date is displayed in orange text when it is within 6 months of expiring, and in red text if it has expired.
