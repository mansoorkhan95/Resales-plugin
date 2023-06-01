<div class="property-slider">
  <ul class="property-slider-list">
    <?php foreach ($property_image_urls as $property_image_url) : ?>
      <li>
        <a href="<?php echo esc_url($property_image_url); ?>" data-lslide="<?php echo esc_url($property_image_url); ?>">
          <img class="property-slider-main-image" src="<?php echo esc_url($property_image_url); ?>" alt="Property Image">
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <ul class="property-slider-thumbs">
    <?php foreach ($property_image_urls as $property_image_url) : ?>
      <li>
        <img class="property-slider-thumb-image" src="<?php echo esc_url($property_image_url); ?>" alt="Property Image">
        <input type="hidden" name="property_image_url" value="<?php echo esc_url($property_image_url); ?>">
      </li>
    <?php endforeach; ?>
  </ul>

  <script>
    jQuery(document).ready(function($) {
      var $slider = $(".property-slider-list").lightSlider({
        item: 1,
        slideMargin: 0,
        autoWidth: false,
        loop: true,
        pager: false,
        controls: true,
        gallery: false,
        thumbItem: 1,
        onSliderLoad: function() {
          $(".property-slider-thumbs").lightSlider({
            item: 6,
            loop: true,
            slideMargin: 10,
            controls: false,
            gallery: false,
            pager: false,
            thumbItem: 1
          });

          $(".property-slider-thumbs li").on("click", function() {
            var index = $(this).index();
            var property_image_url = $(this).find('input[name="property_image_url"]').val();
            $(".property-slider-main-image").attr('src', property_image_url);
          });
        }
      });
    });
  </script>
</div>
