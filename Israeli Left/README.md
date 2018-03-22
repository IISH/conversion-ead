# Israeli Left

## What is Israeli Left?
Israeli Left is a project that contains scripts to convert data from multiple XML files into one EAD file.
This reduces the time required to collect and combine the data provided.

## Install and Run
In order to use the scripts, one needs to install a couple of things beforehand.
* Xampp for running an Apache server.
* Create a 'Data' map to contain information about the sub-directories to handle.
* Create a 'Completed EAD' map for the data completed.
* Add a 'Greenstone Export' map for the data to be converted.

With these installed the script can be run by starting an Apache server. 
With the server running go to localhost/conversion-ead/Israeli Left/ead_convertion.php or localhost/Israeli Left/ead_convertion.php if using the Israeli Left directory directly.

## Usage
To use the script you need to be sure the 'Data' map contains a directories.json file which contains the directories in Greenstone Export to convert.
The format of directories.json should be as follows:

```
{
  "directories": [
        {"directory": "\\The name of the folder"},
        {"directory": "\\The name of the second folder"},
        {"directory": "\\The name of the third folder"},
        {"directory": "\\The name of the fourth folder"}
    ]
}
```

## Features
* Conversion of data from multiple different XML files into EAD files.
* Automatic generation of archive numbers based upon the archive numbers available as displayed in the code.
