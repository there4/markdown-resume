<?php
namespace Resume\Cli;

class TwigFormatters
{
    public static function strpad($string, $length, $position = "center")
    {
        switch ($position) {
            case "left":
                $padding = STR_PAD_RIGHT;
                break;
            case "right":
                $padding = STR_PAD_LEFT;
                break;
            case "center":
            default:
                $padding = STR_PAD_BOTH;
        }

        // This must handle tagged strings for our Console formatting
        // <info>this is a long title</info>
        $total_length = strlen($string);
        $stripped_length = strlen(strip_tags($string));

        $length = $length + $total_length - $stripped_length;

        return str_pad(substr($string, 0, $length), $length, " ", $padding);
    }

    public static function style($string, $format)
    {
        return sprintf('<%2$s>%1$s</%2$s>', $string, $format);
    }
}

/* End of file TwigFormatters */
