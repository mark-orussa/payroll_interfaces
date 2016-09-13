The PayrollInterfaces directory is a repository for payroll interface applications.

Possible reasons for the app not working:
The upload and download locations do not exist or do not have 775 permissions.
The database or table does not exist.
The database user does not exist.
There are new JobXRef codes that are not in the arrays.

In general, there are nested applications that perform various data manipulation, converting CSV files from one system to be compatible with another.