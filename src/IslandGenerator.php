<?php

use JetBrains\PhpStorm\Pure;
use MapGenerator\PerlinNoiseGenerator;

class IslandGenerator
{

//    const COLOR_XYZ = [r,g,b];
    const COLOR_SHORE = [32,91,182];
    const COLOR_COAST = [190,176,130];
    const COLOR_LAND = [90,127,50];
    const COLOR_DARK_LAND = [9,99,12];
    const COLOR_MOUNTAIN = [140,142,123];
    const COLOR_MOUNTAIN_CAP = [235,235,235];

    protected int $size;
    protected false|int $doUpscale = false;

    protected string        $gradient_style = GradientGenerator::STYLE_DEFAULT;
    protected SplFixedArray $gradient;

    protected PerlinNoiseGenerator $noise_generator;

    /** @var \SplFixedArray[] $noise 2d SplFixedArray */
    protected SplFixedArray $noise; // 2d \SplFixesArray[\SplFixedArray]
    protected int            $noise_min  = PHP_INT_MAX;
    protected float          $noise_max  = 0;
    protected float          $noise_diff = 0;

    private GdImage|bool $image;

    /**
     * @param int $size
     */
    public function __construct(int $size)
    {
        $this->size = $size;
        $this->noise_generator = new MapGenerator\PerlinNoiseGenerator();
        $this->noise_generator->setSize($this->size);
        $this->noise_generator->setPersistence(0.8); //map roughness
        $this->setSeed(microtime(true)); // default seed in generator is float micro time
    }

    /**
     * @return \SplFixedArray
     */
    public function generateGradient(): SplFixedArray
    {
        $this->gradient = (new GradientGenerator($this->size))->generate($this->gradient_style);
        return $this->gradient;
    }

    /**
     * @return \SplFixedArray
     */
    public function generateNoiseImage(): SplFixedArray
    {

        $this->noise = $this->noise_generator->generate();

        for ($iy = 0; $iy < $this->size; $iy++) {
            for ($ix = 0; $ix < $this->size; $ix++) {
                $this->noise[$iy][$ix] *= $this->gradient[$iy][$ix];
                $h = $this->noise[$iy][$ix];
                if ($this->noise_min > $h) {
                    $this->noise_min = $h;
                }
                if ($this->noise_max < $h) {
                    $this->noise_max = $h;
                }
            }
        }
        $this->noise_diff = $this->noise_max - $this->noise_min;

        return $this->noise;
    }

    /**
     * @return \GdImage|bool
     */
    public function render(): GdImage|bool
    {
        $this->generateGradient();
        $this->generateNoiseImage();
        $border = [0,1,$this->size-1,$this->size-2];

        $shore        = self::color(self::COLOR_SHORE[0], self::COLOR_SHORE[1], self::COLOR_SHORE[2]);
        $coast        = self::color(self::COLOR_COAST[0], self::COLOR_COAST[1], self::COLOR_COAST[2]);
        $land         = self::color(self::COLOR_LAND[0], self::COLOR_LAND[1], self::COLOR_LAND[2]);
        $dark_land    = self::color(self::COLOR_DARK_LAND[0], self::COLOR_DARK_LAND[1], self::COLOR_DARK_LAND[2]);
        $mountain     = self::color(self::COLOR_MOUNTAIN[0], self::COLOR_MOUNTAIN[1], self::COLOR_MOUNTAIN[2]);
        $mountain_cap = self::color(self::COLOR_MOUNTAIN_CAP[0], self::COLOR_MOUNTAIN_CAP[1], self::COLOR_MOUNTAIN_CAP[2]);

        $this->image = imagecreatetruecolor($this->size,$this->size);

        // add noise pollution and color (pun pun pun)
        if (!empty($this->noise)){
            for ($iy = 0; $iy < $this->size; $iy++) {
                for ($ix = 0; $ix < $this->size; $ix++) {
                    if (in_array($ix,$border)||in_array($iy,$border)){
                        // this is the outer edge/border of the entire image and should be left blank
                        continue;
                    }

                    // calculate height scale
                    $h = 255 * ($this->noise[$iy][$ix] - $this->noise_min) / $this->noise_diff;
                    $h = (int) $h;

                    // @todo allow for input allocation of height thresholds
                    if ($h<153){
                        // deep sea threshold, fill black - will replace with transparent later.
                        imagesetpixel($this->image, $ix, $iy, 0);
                    }
                    elseif ($h<160){
                        // shore threshold
                        imagesetpixel($this->image, $ix, $iy, $shore);
                    }
                    elseif ($h<170){
                        // coast threshold
                        imagesetpixel($this->image, $ix, $iy, $coast);
                    }
                    elseif ($h<194){
                        // land
                        imagesetpixel($this->image, $ix, $iy, $land);
                    }
                    elseif ($h<215){
                        // dark land
                        imagesetpixel($this->image, $ix, $iy, $dark_land);
                    }
                    elseif ($h<230){
                        // mountain threshold
                        imagesetpixel($this->image, $ix, $iy, $mountain);
                    }
                    else{
                        // mountain_cap threshold
                        imagesetpixel($this->image, $ix, $iy, $mountain_cap);
                    }
                }
            }
        }

        // Check if we should do some up-scaling of the image?
        if ($this->getDoUpscale()>0){
            $this->image = imagescale($this->image,$this->getDoUpscale(),$this->getDoUpscale(),IMG_NEAREST_NEIGHBOUR);
        }

        // Replace Black with Alpha.
        imagealphablending($this->image,true);
        imagecolortransparent($this->image,0);
        return $this->image;
    }

    /**
     * @param null|float|int|string $seed
     *
     * @return bool
     */
    public function setSeed (float|int|string|null $seed): bool
    {
        if (is_numeric($seed)||is_string($seed)){
            $this->noise_generator->setMapSeed($seed);
            return true;
        }
        return false;
    }

    /**
     * @param string $gradient_style
     */
    public function setGradientStyle (string $gradient_style = GradientGenerator::STYLE_DEFAULT): void
    {
        if (in_array($gradient_style,GradientGenerator::STYLES)){
            $this->gradient_style = $gradient_style;
        }
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return null
     */
    #[Pure] public function getSeed()
    {
        return $this->noise_generator->getMapSeed();
    }

    /**
     * The actual style of the gradient "square" or "diamond"
     * @return string
     */
    public function getGradientStyle(): string
    {
        return $this->gradient_style;
    }

    /**
     * the gradient array itself with values
     * @return \SplFixedArray
     */
    public function getGradient(): SplFixedArray
    {
        return $this->gradient;
    }

    /**
     * @return \GdImage|bool
     */
    public function getImage(): GdImage|bool
    {
        return $this->image;
    }

    /**
     * @return false|int
     */
    public function getDoUpscale (): false|int
    {
        return $this->doUpscale;
    }

    /**
     * Set a minimum size to upscale the image to as x-by-x or false for no upscale.
     * @param false|int $doUpscale
     */
    public function setDoUpscale (false|int $doUpscale): void
    {
        $this->doUpscale = $doUpscale;
    }

    /**
     * @param int      $r
     * @param null|int $g
     * @param null|int $b
     *
     * @return int
     */
    public static function color(int $r,int $g = null,int $b = null): int
    {
        return ($r * 256 * 256) + (($g ?? $r) * 256) + ($b ?? $r);
    }
}
