# Diamantbewerkers (Vele Handen)

## What is Diamantbewerkers?
Diamantbewerkers is a project that contains scripts to convert data from XML files into CSV files.
This reduces the time required to collect and combine the data provided.

The reason for the script is to process the data inserted via the Vele Handen website: https://velehanden.nl/projecten/bekijk/details/project/mbi

## Install and run
In order to use the scripts, one needs to install a couple of things beforehand.
* PHP on the device where the script will be used.
* Download the script (vele_handen.php) from this repository.

With these installed the script can be run by calling the script via command line. 
This is done by opening a Command Prompt or using Powershell. When opened you need to navigate to the directory
where the script is kept. This can be done by using the commands ``cd`` and ``dir/ls``.
* ``cd``: C:\> ``cd Desktop\VeleHanden\`` -> will navigate to the directory given.
* ``ls``: C:\> ``ls`` -> will display all files present in the current directory. (Powershell)
* ``dir``: C:\> ``dir`` -> will display all files present in the current directory. (Command Prompt)

In order to run the script, after navigating to the correct directory, a specific command needs to be used. This is 
the php command. This command tells the computer to run a php script, along with some variables/parameters. The command
is called by typing: ``php -f .\vele_handen.php export-velehanden.xml`` where -f is the argument to tell the php command 
a file will be parsed and executed, in this case ``vele_handen.php``. The file ``export-velehanden.xml`` is the file which
will be converted into a CSV file.

After the command is typed in, the ``enter`` key can be pressed which will tell the command line to execute it. After the script is done,
the file can be found in the same folder as where the xml file is located.

## Usage
In order to use the script, the data provided needs to be in the right format. This to make sure the script can run as intended
and does not display a whole load of errors.

To make sure the data is in the right format, you need to check if it is the same as displayed below.

```
<?xml version="1.0" encoding="UTF-8"?>
<velehanden>
 <response>
  <status>
   <code>CODE</code>
   <message>MESSAGE</message>
  </status>
  <project>PROJECT</project>
  <startdate>STARTDATE</startdate>
  <enddate>ENDDATE</enddate>
  <limit>LMIT</limit>
  <hits>HITS</hits>
  <returned>RETURNED</returned>
  <data>
   <personen>
    <folio serie_name="SERIE_NAME" serie_title="SERIE TITLE" image_title="IMAGE TITLE" image_uuid="IMAGE UUID">
     <scan_id>SCAN_ID</scan_id>
     <image_id>IMAGE_ID</image_id>
     <uuid>UUID</uuid>
     <werk>
     [
        {toelatings_day:TOELATINGS_DAY,toelatings_month:TOELATINGS_MONTH,toelatings_year:TOELATINGS_YEAR,
        name:NAME,atelier:ATELIER,street:STREET,streetnumber:STREETNUMBER,streetnumber_addition:STREETNUMBER ADDITION,
        city:CITY,fabrieksdatum_day:FABRIEKSDATUM_DAY,fabrieksdatum_month:FABRIEKSDATUM_MONTH,
        fabrieksdatum_year:FABRIEKSDATUM_YEAR,step:STEP,key:KEY,suborder:SUBORDER}
        ,
        {toelatings_day:TOELATINGS_DAY,toelatings_month:TOELATINGS_MONTH,toelatings_year:TOELATINGS_YEAR,
        name:NAME,atelier:ATELIER,street:STREET,streetnumber:STREETNUMBER,streetnumber_addition:STREETNUMBER ADDITION,
        city:CITY,fabrieksdatum_day:FABRIEKSDATUM_DAY,fabrieksdatum_month:FABRIEKSDATUM_MONTH,
        fabrieksdatum_year:FABRIEKSDATUM_YEAR,step:STEP,key:KEY,suborder:SUBORDER}
     ]
     </werk>
     <kaartnummer>[KAARTNUMMER]</kaartnummer>
     <OKNo>[OKNO]</OKNo>
     <leerling>
     [
        {dummy:DUMMY,voornaam:VOORNAAM,tussenvoegsel:TUSSENVOEGSEL,achternaam:ACHTERNAAM,
        meisjesnaam:MEISJESNAAM,birth_date:BIRTH_DATE,birth_month:BIRTH_MONTH,birth_year:BIRTH_YEAR,
        geboorteplaats:GEBOORTEPLAATS,step:STEP,key:KEY,suborder:SUBORDER}
     ]
     </leerling>
     <adres>
     [
        {street_name:STREET_NAME,street_number:STREET_NUMBER,street_addition:STREET_ADDITION,
        city:CITY,day:DAY,month:MONTH,year:YEAR,step:STEP,key:KEY,suborder:SUBORDER}
     ]
     </adres>
     <branche>[BRANCHE]</branche>
     <einde_leertijd>
     [
        {day:DAY,month:MONTH,year:YEAR,step:STEP,key:KEY,suborder:SUBORDER}
     ]
     </einde_leertijd>
     <proef_afgelegd_bij>[PROEF_AFGELEGD_BIJ]</proef_afgelegd_bij>
     <lidmaatschapsdatum>
     [
        {day:DAY,month:MONTH,year:YEAR,step:STEP,key:KEY,suborder:SUBORDER}
     ]
     </lidmaatschapsdatum>
     <naam_vakbond>[NAAM_VAKBOND]</naam_vakbond>
     <vakgroepnummer>[VAKGROEPNUMMER]</vakgroepnummer>
     <lidmaatschapsnummer>[LIDMAATSCHAPNUMMER]</lidmaatschapsnummer>
     <broeders_lidmaatschapsnummers>[BROEDERS_LIDMAATSCHAPSNUMMERS_1, BROEDERS_LIDMAATSCHAPSNUMMERS_2]</broeders_lidmaatschapsnummers>
     <zusters_lidmaatschapsnummers>[ZUSTERS_LIDMAATSCHAPSNUMMERS_1, ZUSTERS_LIDMAATSCHAPSNUMMERS_2]</zusters_lidmaatschapsnummers>
     <zoon_of_dochter_van>
     [
        {zoon_of_dochter_van:ZOON_OF_DOCHTER_VAN,identifier:IDENTIFIER}
     ]
     </zoon_of_dochter_van>
     <vader_of_moeder>
     [
        {vader_of_moeder:VADER_OF_MOEDER,identifier:IDENTIFIER}
     ]
     </vader_of_moeder>
     <ouder>
     [
        {dummy:DUMMY,voornaam:VOORNAAM,tussenvoegsel:TUSSENVOEGSEL,achternaam:ACHTERNAAM,
        meisjesnaam_ouder:MEISJESNAAM_OUDER,birth_date:BIRTH_DATE,birth_month:BIRTH_MONTH,birth_year:BIRTH_YEAR,
        geboorteplaats:GEBOORTEPLAATS,step:STEP,key:KEY,suborder:SUBORDER}
     ]
     </ouder>
     <ouder_naam_vakbond>[OUDER_NAAM_VAKBOND]</ouder_naam_vakbond>
     <ouder_vakgroepnummer>[OUDER_VAKGROEPNUMMER]</ouder_vakgroepnummer>
     <ouder_lidmaatschapsnummer>[OUDER_LIDMAATSCHAPSNUMMER]</ouder_lidmaatschapsnummer>
     <ERNo>[ERNO]</ERNo>
     <opmerkingen>[OPMERKINGEN]</opmerkingen>
     <serie_name>SERIE_NAME</serie_name>
     <serie_titel>SERIE_TITEL</serie_titel>
     <image_title>IMAGE_TITLE</image_title>
     <image_uuid>IMAGE_UUID</image_uuid>
    </folio>
   </personen>
  </data>
 </response>
</velehanden>
```

## Features
* Conversion of data from XML files into CSV files.

