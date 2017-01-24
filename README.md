# WSUWP VALS Custom Roles

[![Build Status](https://travis-ci.org/washingtonstateuniversity/WSUWP-Plugin-VALS-Roles.svg?branch=master)](https://travis-ci.org/washingtonstateuniversity/WSUWP-Plugin-VALS-Roles)

A WordPress plugin that provides custom roles for the VALS Program site

## Provided Roles

### VALS Center Admin
Users with this role have `read`, `list_users`, and `create_users` capabilities. They can:
* (and should) be assigned to a VALS Center;
* add new users, who will be:
  * associated with the VALS Center Admin's respective VALS Center; and
  * assigned the VALS Registered Trainee role;
* view the Users list, but can only see users who are:
  * associated with the VALS Center Admin's respective VALS Center; and
  * assigned to one of the roles listed here.

### VALS Certified
Users with this role have `read` capabilities. They can:
* be assigned to a VALS Center;
* be assigned a Certification Date;
* view only their own profile.

When a user's role is changed to VALS Certified, the current date is automatically assigned as the user's Certification Date (which can be manually adjusted if needed).

### VALS Registered Trainee
Users with this role have `read` capabilities. They can:
* be assigned to a VALS Center;
* view only their own profile.

## Other Features

A custom taxonomy is provided for associating users with a VALS Center.

A stylesheet that hides a majority of the default interface is enqueued for profile pages belonging to users with any of the provided roles. The *First Name*, *Last Name*, and *Account Management* fields are left available.

*Date Certified* and *Center* columns are added to the Users list. In both the *Date Certified* column and in *VALS Certified* users profiles, the certification date is displayed in orange text when it is within 6 months of expiring, and in red text if it has expired.
