(function ($, Drupal) {
  let selectedMap = {};
  Drupal.behaviors.ckcHodnoceniBehavior = {
    attach: function (context, settings) {
      // Initialize status of  selected values for ckc_hodnoceni.
      $('select[name^="order_"]').once('ckcHodnoceniBehavior').each(function(i, el) {
        selectedMap[this.name] = this.value;
      });
      // Fire after new value is selected.
      $(document).once('ckcHodnoceniBehavior').on('change', 'select', function(ev) {
        let oldValue = selectedMap[this.name];
        let selectedValue = this.value;
        selectedMap[this.name] = selectedValue;
        // Disable selected value on every other select option.
        $('select[name^="order_"]').each(function(i, el) {
          if (ev.target === el) {
            return;
          }
          $('option[value="' + oldValue + '"]', el)
            .removeAttr('disabled')
            .removeClass('selected');
          if (selectedValue === '---') {
            return;
          }
          $('option[value="' + selectedValue + '"]', el)
            .attr('disabled', 'disabled')
            .addClass('selected');

          // if (selectedValue === '---'){
          //   return;
          // }
          // if (ev.target === el) {
          //   return;
          // }
          // $('option[value="' + selected_value + '"]')
          //   .attr('disabled', 'disabled')
          //   .addClass('selected');
        });
      });
      // console.log('~~~>', context);
      // $('select[name^="order_"]', context).once('ckcHodnoceniBehavior').each(function(i, el) {
      //   // console.log('~~~>', i, settings.ckcHodnoceni.works);
      //   // console.log('--->', i, el.style = 'border: 1px solid red;');
      //   // Apply the myCustomBehaviour effect to the elements only once.
      // });
    }
  };
})(jQuery, Drupal);
