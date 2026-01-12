<?php
if (!defined("ABSPATH")) {
    exit();
}
$prevClass = $currentOffset == 0 ? "ir-hidden" : "";
$nextClass = ($currentOffset + 1) == $countPages ? "ir-hidden" : "";
?>
<a href="#!" class="custom-pagination__btn custom-pagination__btn--arrow ir-prev-page <?php esc_attr_e($prevClass); ?> <?php echo "ir-" . esc_attr($type) . "-pagination"; ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="8" height="13" viewBox="0 0 8 13" fill="none">
        <path d="M6.38928 13C6.78815 13 7.1873 12.8528 7.49917 12.5568C8.1438 11.9438 8.1695 10.924 7.55679 10.2791L3.95285 6.48873L7.55312 2.72462C8.16809 2.08196 8.14549 1.06219 7.50256 0.44694C6.85934 -0.168312 5.83985 -0.145431 5.22488 0.497505L0.562469 5.37122C-0.0318794 5.99268 -0.0330095 6.97149 0.558796 7.59494L5.22093 12.4992C5.53816 12.8316 5.96273 13 6.38928 13Z" fill="currentColor" />
    </svg>
</a>
<?php
for ($i = 0; $i < $countPages; $i++) {
    $page = $i + 1;
    $offset = $i ? $i : "";
    $classActive = ($i === $currentOffset) ? "custom-pagination__btn--active" : "";
    ?>
    <a href="#!" class="custom-pagination__btn <?php esc_attr_e($classActive); ?> <?php echo "ir-page-" . (int) $page; ?> <?php echo "ir-" . esc_attr($type) . "-pagination"; ?>" data-offset="<?php echo (int) $i; ?>"><?php echo (int) $page; ?></a>
    <?php
}
?>                
<a href="#!" class="custom-pagination__btn ir-next-page <?php esc_attr_e($nextClass); ?> <?php echo "ir-" . esc_attr($type) . "-pagination"; ?>">
    <svg xmlns="http://www.w3.org/2000/svg" style="transform: rotateY(180deg);" width="8" height="13" viewBox="0 0 8 13" fill="none">
        <path d="M6.38928 13C6.78815 13 7.1873 12.8528 7.49917 12.5568C8.1438 11.9438 8.1695 10.924 7.55679 10.2791L3.95285 6.48873L7.55312 2.72462C8.16809 2.08196 8.14549 1.06219 7.50256 0.44694C6.85934 -0.168312 5.83985 -0.145431 5.22488 0.497505L0.562469 5.37122C-0.0318794 5.99268 -0.0330095 6.97149 0.558796 7.59494L5.22093 12.4992C5.53816 12.8316 5.96273 13 6.38928 13Z" fill="currentColor" />
    </svg>
</a>
<input type="hidden" class="ir-count-pages" value="<?php echo (int) $countPages; ?>" />