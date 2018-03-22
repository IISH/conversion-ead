# Staatsarchief

##What is Staatsarchief?
Staatsarchief is a project that contains scripts to convert data from multiple different PHP (HTML) files into one EAD file.
This reduces the time required to collect and combine the data provided.

##Install and run
In order to use the scripts, one needs to install a couple of things beforehand.
* Xampp for running an Apache server.
* Create a 'data' map for the data to be converted.
* Create a 'result' map for the data to be saved.

With these installed the script can be run by starting an Apache server. 
With the server running go to localhost/conversion-ead/Staatsarchief/staatsarchief.php or localhost/Staatsarchief/staatsarchief.php if using the Staatsarchief directory directly.

##Usage
To use the script you need to make sure the data provided is created according the correct format.
The data needs to be a PHP file format with HTML code inside.
The HTML needs to have multiple modules in the file, one for the general info about the file and multiple more
for the items in that file.

The format for the general info is as follows:
```
<div class="group">
    <div class="grouptitle">Amsterdam</div>
    <div class="groupcallno">SAVRZ### Doos 001 Map 3)</div>
    <div class="groupsize">280 centimeter</div>
    <div class="groupperiod">1955 - 1966</div>
</div>
```

The format for the item is as follows:
```
<div class="item">
    <div class="title"><a name="title">title</a></div>
    <div class="callno">(SAVRZ071 Doos 001 Map 1.11)</div>
    <div class="label">Materiaal</div>
    <div class="format">pamfletten, strips uit Nederland.</div>
    <div class="label">Periode</div>
    <div class="period"> 1991</div>
    <div class="label">Geografisch onderwerp</div>
    <div class="geographical"> Nederland: Den Haag</div>
</div>
```

The general format of a file should be as follows:
```
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>Amsterdam</title>
</head>
<body>
    <div class="main">
        <table cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td width="140" class="menu">
                    <div class="menu">
                        <span class="navig"><a href="../../index.php" class="menu">Home</a></span><br>
                        <span class="navig"><a href="../../collecties.php" class="menu">Collecties</a></span><br>
                    </div>
                    <div class="submenu">
                        <span class="searchopt"><a href="../index.php" class="menu">Archieven</a></span><br>
                        <span class="searchopt"><a href="../index.php#amsterdam" class="menu">Algemeen</a></span><br>
                    </div>
                </td>
                <td class="content">
                    <div class="group">
                        Group info....
                    </div>
                    
                    <div class="item">
                        Item info...
                    </div>
                    
                    etc.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
```

The files provided should be place in a 'data/staatsarchief' folder so the script knows where to find the data.
And in order to start the application correctly, the main file should be called 'collecties.php' and should be placed in 'data/staatsarchief/'.
This file is the leading file for the script.

##Features
* Conversion of data from multiple different PHP files into EAD files.
* Automatic generation of archive numbers based upon the archive numbers available as displayed in the code.

