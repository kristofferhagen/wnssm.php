wnssm.php
=========
Wireless Network Signal Strength Monitor is a console text-only application showing the signal strength of available access points.

Usage
-----
```bash
root@computer:~# ./wssm.php INTERFACE [--essid=ESSID] [--address=ADDRESS]
```

Example Output
--------------
```bash
root@computer:~# ./wnssm.php wlan0
 Network1            1E:AT:DE:AD:BE:EF [|||||||||||||||        ] 48/70 -62 dBm
 Network2            1A:TE:DE:AD:BE:EF [||||||||||||||||       ] 50/70 -60 dBm
```
