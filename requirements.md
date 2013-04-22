Database Server
===============================================
- Create database
  - Database Name: Sorapro_Application
- Create database user and associate with db as db/owner
- Run database creation script against database
- Recommend backup process

Web Server
===============================================
- Repo Available at : FutureSolutionsTechnology/sorapro-cuny
- Install web platform installer
  - Whatever current version is available.
- Install PHP (via web platform installer)
  - 5.3 or greater
	- Tested against 5.3 and 5.4
- Modify php.ini
  - error_log = C:\Sorapro\sorapro-cuny\PHP\Log\php54_errors.log
	- include_path = "C:\Sorapro\sorapro-cuny\PHP\Inlcude"
	- upload_tmp_dir = C:\Sorapro\sorapro-cuny\PHP\Temp
	- upload_max_filesize = 25M
	- session.save_path = C:\Sorapro\sorapro-cuny\PHP\Session
	- date.timezone=America/New_York
- Modify initilaize file
  - DB_CONN 
  - DB_USER 
  - DB_PASS 
  - BASE_PATH C:\Sorapro\sorapro-cuny\
  - PATH_FILE_PROCESSING C:\Sorapro\sorapro-cuny\File Processing
  - PATH_FILE_STORAGE C:\Sorapro\sorapro-cuny\File Storage
- Configure IIS
  - 7.5 expected
	- Set up site instance.
	- Direct the home directory to App\Consume
	- Bind IP and domain. http://das.sustainable.cuny.edu/