# HBM

##What is HBM?
HBM is a project that contains scripts to convert data from two different CSV files into one EAD file.
This reduces the time required to collect and combine the data provided.

##Install and run
In order to use the scripts, one needs to install a couple of things beforehand.
* PHPExcel to convert data between Excel and PHP.
* Composer is required for the script to work.
* A 'vendor' directory containing composer, paragonie and ramsey.
* Xampp for running an Apache server.
* Create a 'Data' map for the data to be converted.

With these installed the script can be run by starting an Apache server. 
With the server running go to localhost/conversion-ead/HBM or localhost/HBM if using the HBM directory directly.

##Usage
To use the script you need to make sure the data provided is created according the correct format.
The data provided consists of a Collecties.csv and a Fotos.csv file, which will be converted into an EAD.

The format for the Collecties.csv is as follows:
```
| codecollectie | collectienaam | plaats | datum | contract | openbaar | opmerkingen | telefoon | biografie | fld_source | achternaam | voorletters | straat | postcode | opmerkingen 2 | code_user |
```
The format for the Fotos.csv is as follows:
```
| codefoto | codecollectie | volgnummer | plaatsnummerIISG | materiaalsoort | afmetingen | fotograaf | fotobureau/studio | plaats | datum | beschrijving | vrij | annotatie | code_user | beperktopenbaar | fld_source | Nationaliteiten | Organisaties | Personen | Trefwoorden |
```

The files provided should be place in a 'Data' folder so the script knows where to find the data.

##Features
* Conversion of data from two different CSV files into EAD files.
* Export of image names linked to the archive names.
* Automatic generation of archive numbers based upon the archive numbers available as displayed in the code.

