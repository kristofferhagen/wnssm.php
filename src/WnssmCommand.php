<?php

namespace KristofferHagen\Wnssm;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WnssmCommand extends Command
{
    /**#@+
     * Regular expressions used for matching
     */
    const REGEX_ADDRESS   = '/Address: ((?:[0-9a-f]{2}[:-]){5}[0-9a-f]{2})/i';
    const REGEX_CHANNEL   = '/Channel:(\d)/';
    const REGEX_FREQUENCY = '/Frequency:(.*)\s\(/';
    const REGEX_QUALITY   = '/Quality\=([0-9]{2}\/[0-9]{2})/';
    const REGEX_SIGNAL    = '/Signal level\=(.*)/';
    const REGEX_ESSID     = '/ESSID:"([^"]+)"/';
    /**#@-**/

    protected function configure()
    {
        $this
            ->setName('wnssm')
            ->setDescription('Show signal strength of available wireless networks')
            ->addArgument(
                'interface',
                InputArgument::REQUIRED,
                'Interface to scan'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interface = $input->getArgument('interface');

        exec('iwlist '.$interface.' scanning', $out);

        $out = implode("\n", $out);
        $out = str_replace('                    ', '', $out);
        $cells_raw = explode('          Cell ', $out);
        unset($cells_raw[0]);

        $cells = array();

        foreach ($cells_raw as $cell) {
            $cell_id = substr($cell, 0, 2);

            if (preg_match(self::REGEX_ADDRESS, $cell, $matches)) {
                $cells[$cell_id]['address'] = $matches[1];
            }

            if (preg_match(self::REGEX_CHANNEL, $cell, $matches)) {
                $cells[$cell_id]['channel'] = $matches[1];
            }

            if (preg_match(self::REGEX_FREQUENCY, $cell, $matches)) {
                $cells[$cell_id]['frequency'] = $matches[1];
            }

            if (preg_match(self::REGEX_QUALITY, $cell, $matches)) {
                $cells[$cell_id]['quality'] = $matches[1];
            }

            if (preg_match(self::REGEX_SIGNAL, $cell, $matches)) {
                $cells[$cell_id]['level'] = $matches[1];
            }

            if (preg_match(self::REGEX_ESSID, $cell, $matches)) {
                $cells[$cell_id]['essid'] = $matches[1];
            }
        }

        foreach ($cells as $key => $cell) {
            $address   = $cell['address'];
            $channel   = $cell['channel'];
            $frequency = $cell['frequency'];
            $quality   = $cell['quality'];
            $essid     = $cell['essid'];

            $quality_parts   = explode('/', $quality);
            $quality_len_max = 24;
            $quality_len     = $quality_parts[1] / $quality_len_max;
            $quality_val     = round($quality_parts[0] / $quality_len);
            $quality_max     = round($quality_parts[1] / $quality_len);
            $quality_min     = 1;
            $quality_str     = NULL;

            for ($i = $quality_min; $i < $quality_val; $i++) {
                $quality_str .= '|';
            }
            for ($i = $quality_max; $i > $quality_val; $i--) {
                $quality_str .= ' ';
            }

            $pos_1 = ($quality_len_max / 3) * 0;
            $pos_2 = ($quality_len_max / 3) * 1;
            $pos_3 = ($quality_len_max / 3) * 2;
            $pos_4 = ($quality_len_max / 3) * 3;

            // Color signal quality bars
            $quality_str = substr_replace($quality_str, "\033[0m",    $pos_4, 0);
            $quality_str = substr_replace($quality_str, "\033[32;1m", $pos_3, 0);
            $quality_str = substr_replace($quality_str, "\033[33;1m", $pos_2, 0);
            $quality_str = substr_replace($quality_str, "\033[31;1m", $pos_1, 0);

            $line = ' ';
            $line .= $cell['essid'];

            $len = strlen($cell['essid']);
            for ($i = $len; $i < 20; $i++) {
                $line .= ' ';
            }

            $line .= $cell['address'];

            $line .= ' [' . $quality_str . '] ';
            $line .= $cell['quality'];
            $line .= ' ';
            $line .= "\033[1m" . $cell['level'] . "\033[0m";

            $output->writeln($line);
        }
    }
}
