# rest-file-manager

A PHP file manager with a REST interface

## requirements

* .env file to set files root and other needed constants
* files will be served if accessed directly
* public methods
    * GET /info/{path} - get information about a file or directory (size, mime-type, folder contents, etc)
    * POST /upload - [file,path] - upload a file to path
        * `file`: a received via upload
        * `path`: a string path relative to files root
    * POST /add-folder - [path] - creates a directory
        * `path`: a string path relative to files root
    * POST /rename - [old_file_path,new_file_path]
        * `old_file_path`: a string path relative to files root pointing to an existent file or folder
        * `new_file_path`: a string path relative to which `old_file_path` will be renamed
    * POST /copy - [source,dest] - copies `source` to `destination` (files)
        * `source`: a string path relative to files root pointing to an existent file
        * `dest`: a string path relative to which `source` will be copied
    * POST /copy-folder - [source,dest] - copies `source` to `destination` (folder)
        * `source`: a string path relative to files root pointing to an existent folder
        * `dest`: a string path relative to which `source` will be copied
    * POST /delete - [path] - delete a file or a folder if empty
        * `path`: a string path to the file/folder to be deleted
    * POST /force-delete - [path] - delete a file or a folder even if empty
        * `path`: a string path to the folder to be deleted
    * POST /add-user - [username,permissions_string] - adds the user and responds with an API key
        * `username`: the new username
        * `permissions_string`: cf-rf-uf-df-cu-ru-uu-du, if no permissions then it will be marked with xx, eg: cf-xx-xx-xx-cu-xx-xx-xx will only allow creation of files, folders and users
            * cf: create file/folder
                * POST /upload - [file,path]
                * POST /copy - [source,dest]
                * POST /copy-folder - [source,dest]
            * rf: create file/folder
                * GET /info/{path}
            * uf: create file/folder
                * POST /rename - [old_file_path,new_file_path]
            * df: create file/folder
                * POST /delete - [path]
                * POST /force-delete - [path]
            * cu: create user
                * POST /add-user - [username,permissions_string]
            * ru: read user
                * GET /user/{username}
                * GET /users
            * uu: update users permissions
                * POST /update-user
            * du: delete user
                * POST /delete-user
    * GET /user/{username} - get user info (permissions)
    * GET /users - get users list
    * POST /update-user - [username,permissions_string] - update the user
    * POST /delete-user - [username] - deletes the user
    
## limitations

* 5.4 <= PHP <= 7.3
* an .users.json file for user and permissions management
    * example: `{"lucian": "cf-rf-uf-df-cu-ru-uu-du", "mohamed": "cf-xx-xx-xx-cu-xx-xx-xx"}`
* no framework
* 1-3 php files max (index, functions, class)
* timeframe: 24 hours

(Could we use packages like vlucas/phpdotenv?)

