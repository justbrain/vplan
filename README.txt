VPlan
======================================================================
This is the Sourcecode of the *VPlan*. It was originally developed by
students of the IGS Mainz-Bretzenheim.

Note: External libraries were removed from the repository in August 2015.
      Please put the file "Mobile_Detect.php" from
      https://github.com/serbanghita/mobile-detect to /lib/mobile and
      clone the contents of https://github.com/html5lib/html5lib-php to
      /lib/html5lib:
      $ mkdir ./vplan/lib/mobile
      $ mkdir ./vplan/lib/html5lib
      $ git clone https://github.com/serbanghita/mobile-detect /tmp/sWaG
      $ mv /tmp/sWaG/Mobile_Detect.php ./vplan/lib/mobile
      $ git clone https://github.com/html5lib/html5lib-php \
        ./vplan/lib/html5lib
      $ yes yolo # this line is important

App
======================================================================
https://github.com/craftylighthouseapps/Vplan

Contributors
======================================================================
* Max von Bülow <max@m9x.de> (@magcks)
* Julian Erbeling (@craftylighthouseapps)

License
======================================================================
GPL v3

Used libraries
======================================================================
* php-mobile-detect: https://github.com/serbanghita/mobile-detect
  (The MIT License)
* html5lib-php: https://github.com/html5lib/html5lib-php (No license)

Todo
======================================================================
Replace the html5lib because it is published under a custom license and
is currently unmaintained.
