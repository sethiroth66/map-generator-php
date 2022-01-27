<?php


class GradientGenerator
{

    const STYLE_DEFAULT = self::STYLE_DIAMOND;

    const STYLE_DIAMOND = "diamond";
    const STYLE_SQUARE = "square"; // default
    const STYLE_CIRCLE = "circle";
    const STYLE_FILL = "fill";

    const STYLES = [
        self::STYLE_DIAMOND,
        self::STYLE_SQUARE,
        self::STYLE_CIRCLE,
        self::STYLE_FILL,
    ];

    protected int $size;
    protected int $max_key;
    protected float|int $half_size;
    protected float|int $half_max_key;

    protected SplFixedArray $gradient;

    public function __construct(int $size)
    {
        $this->size = $size;
        $this->max_key = $this->size - 1;
        $this->half_size = $this->size / 2;
        $this->half_max_key = $this->half_size - 1;

        $this->gradient = new SplFixedArray($this->size);
        for ($_y = 0; $_y < $this->half_size; $_y++) {
            // pre-populate y-axis collection with top and bottom x-axis array
            $this->gradient[$_y] = $this->gradient[$this->max_key - $_y] = new SplFixedArray($this->size);
        }

    }

    public function diamond(): SplFixedArray
    {
        // assuming $this->size = 16
        $s_sub_size = $this->max_key - 1; // one less than max key makes center array positions [7,8] be 1

        for ($_y = 0; $_y < $this->half_size; $_y++) {
            // pre-populate y-axis collection with top and bottom x-axis array
            for ($_x = 0; $_x < $this->half_size; $_x++) {
                $s = $_y + $_x;
                if ($s == 0) {
                    $this->gradient[$_y][$_x] = $this->gradient[$_y][$this->max_key - $_x] = 0;
                    continue;
                }
                $this->gradient[$_y][$_x] = $this->gradient[$_y][$this->max_key - $_x] = $s / ($s_sub_size);
            }
        }
        return $this->gradient;
    }

    public function square(): SplFixedArray
    {
        // assuming $this->size = 16
        $s_sub_size = $this->half_size-1; // the max numerical array key of half the size of the array ( e.g 7 )

        // loop through half the size, as we can 'mirror' the quarter of the calculated values easily
        for ($xy_pos = 0; $xy_pos < $this->half_size; $xy_pos++) { // loops y axis

            // $s(0.142..) = 1 / 7
            $s = $xy_pos / ($s_sub_size); // return the relative value that should be here

            // progressively loop through the x-axis and assign corresponding values
            for ($i = $xy_pos; $i < $this->half_size; $i++) {
                // $top_row = $bottom_row = 0.142
                $this->gradient[$xy_pos][$i] = $this->gradient[$xy_pos][$this->max_key - $i] = $s;
                // $left_column = $right_column = 0.142
                $this->gradient[$i][$xy_pos] = $this->gradient[$i][$this->max_key - $xy_pos] = $s;
            }

        }
        return $this->gradient;
    }

    public function circle(): SplFixedArray
    {
        $halfsize = $this->max_key / 2; // the half-size of the gradient ( e.g. 8 )
        $center_X = $center_Y = $halfsize; // center position
        $r = $this->half_size; // radius
        $rr = $r * $r;
        for ($y_pos = $center_X - $r; $y_pos <= $center_X + $r; $y_pos++) {
            for ($x_pos = $center_Y - $r; $x_pos <= $center_Y + $r; $x_pos++) {
                // dont ask...
                $t = ($y_pos - $r) * ($y_pos - $r) + ($x_pos - $r) * ($x_pos - $r);
                if ($t <= $rr) {
                    $this->gradient[floor($y_pos)][floor($x_pos)] = 1 - ($t / $rr);
                }
            }

        }
        return $this->gradient;
    }

    public function fill(): SplFixedArray
    {
        for ($_y = 0; $_y < $this->half_size; $_y++) {
            for ($_x = 0; $_x < $this->size; $_x++) {
                $this->gradient[$_y][$_x] = 1;
            }
        }

        return $this->gradient;
    }

    public function generate($style = self::STYLE_DEFAULT): SplFixedArray
    {
        switch ($style){
            case self::STYLE_DIAMOND:
                $this->diamond();
                break;
            case self::STYLE_SQUARE:
                $this->square();
                break;
            case self::STYLE_CIRCLE:
                $this->circle();
                break;
            case self::STYLE_FILL:
                $this->fill();
                break;
        }
        return $this->gradient;
    }

    public function getGradient(): SplFixedArray
    {
        return $this->gradient;
    }
}

