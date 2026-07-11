<?php

return [

    /*
     * Max size per uploaded file, in kilobytes.
     */
    'max_file_size_kb' => env('ATTACHMENT_MAX_FILE_SIZE_KB', 10240),

    /*
     * Max number of files allowed per upload request.
     */
    'max_files' => env('ATTACHMENT_MAX_FILES', 5),

];
