(function ($, Drupal) {
  let selectedMap = {};
  Drupal.behaviors.ckcHodnoceniBehavior = {
    attach: function (context, settings) {
      let selectedInputElementName = '';
      let selectedValues = drupalSettings.ckcHodnoceni.selectedValues;

      function inputValueValid(inputValue, inDrupalSettings = true) {
        return inputValue.length === 3 && drupalSettings.ckcHodnoceni.worksKeys.includes(inputValue);
      }

      function getInputName(name) {
        return `input[name="${name}"].form-text`;
      }

      function selectWork(inputTarget, inputName, inputValue) {
        let workItem = $(`.work-item-${inputValue}`, '#ckc-rate-form .works-wrapper');
        workItem
          .addClass('selected')
          .data('toInput', inputName)
          .attr('data-to-input', inputName)
          .parent()
          .find('.work-item-rank')
          .text(inputTarget.parent().parent().find('label.form-item').text());
      }

      function unselectWork(inputValue) {
        selectedValues.map.byInputValue[inputValue].inputName = '';
        let oldWorkItem = $(`.work-item-${inputValue}`, '#ckc-rate-form .works-wrapper');
        oldWorkItem
          .removeClass('selected')
          .removeData('toInput')
          .removeAttr('data-to-input')
          .parent()
          .find('.work-item-rank')
          .text('');
      }

      function setInputValid(target, inputName, inputValue) {
        target
          .removeClass('error')
          .addClass('valid-input');
        selectWork(target, inputName, inputValue)
      }

      function setInputInvalid(target, inputName, newInputValue, oldInputValue) {
        if (selectedValues.map.byInputValue[oldInputValue] && selectedValues.map.byInputValue[oldInputValue].inputName !== '') {
          unselectWork(oldInputValue);
          recheckInputsValidity(oldInputValue);
        }
        if (newInputValue.length === 0) {
          setInputEmpty(target);
          return;
        }
        target
          .removeClass('valid-input')
          .addClass('error');
      }

      function setInputEmpty(target) {
        target
          .removeClass('valid-input')
          .removeClass('error');
      }

      function recheckInputsValidity(oldValue) {
        for (inputName in selectedValues.map.byInputName) {
          if (selectedValues.map.byInputName[inputName].value === '') {
            continue;
          }
          if (selectedValues.map.byInputName[inputName].value !== oldValue) {
            continue;
          }
          if (selectedValues.map.byInputValue[oldValue]['inputName'] === inputName) {
            break;
          }
          processInput($(getInputName(inputName), '#ckc-rate-form'));
          break;
        }
      }

      function processInput(target) {
        let inputName = target.attr('name');
        let newInputValue = target.val();
        let oldInputValue = selectedValues.map.byInputName[inputName].value;
        // Nothing to do (no change here).
        if (newInputValue === oldInputValue && selectedValues.map.byInputName[inputName].valid) {
          selectedValues.map.byInputName[inputName].valid = true;
          selectedValues.map.byInputValue[newInputValue].inputName = inputName;
          setInputValid(target, inputName, newInputValue);
          return;
        }
        selectedValues.map.byInputName[inputName].value = newInputValue;
        if (inputValueValid(newInputValue)) {
          // Nothing to do (input in inputValue map is already valid).
          if (selectedValues.map.byInputValue[newInputValue].inputName === inputName)  return;
          // Input in inputValue is now valid.
          if (selectedValues.map.byInputValue[newInputValue].inputName === '') {
            selectedValues.map.byInputName[inputName].valid = true;
            selectedValues.map.byInputValue[newInputValue].inputName = inputName;
            setInputValid(target, inputName, newInputValue);
            return;
          }
        }
        selectedValues.map.byInputName[inputName].valid = false;
        setInputInvalid(target, inputName, newInputValue, oldInputValue);
      }

      $('input[name^="order_"].form-text', '#ckc-rate-form').once('ckcHodnoceniBehavior')
        .focusin(
          (ev) => {
            let target = $(ev.target);
            if (selectedInputElementName) {
              $(selectedInputElementName, '#ckc-rate-form').removeClass('selected-input');
            }
            selectedInputElementName = getInputName(target.attr('name'));
            $(selectedInputElementName, '#ckc-rate-form').addClass('selected-input');
          }
        )
        .keypress(
          (ev) => {
            let target = $(ev.target);
            let keyCode = ev.which;
            // Space delete whole input.
            if (keyCode === 32) {
              target.val('');
            }
            // Only backspace and 0-9 are allowed.
            if (keyCode !== 8 && (keyCode < 48 || keyCode > 57)) return false;
          }
        )
        .keyup((ev) => {
          processInput($(ev.target));
        });

      $('.work-item', $('.works-wrapper')).once('ckcHodnoceniBehavior')
        .hover(
          (ev) => {
            let target = $(ev.target);
            target.parent().addClass('yellow');
            if (target.data('toInput')) {
              $(getInputName(target.data('toInput')), '#ckc-rate-form').addClass('input-with-value');
            }
          },
          (ev) => {
            let target = $(ev.target);
            target.parent().removeClass('yellow');
            if (target.data('toInput')) {
              $(getInputName(target.data('toInput')), '#ckc-rate-form').removeClass('input-with-value');
            }
          },
        );

      $('input[name="exclude_first_place"].form-checkbox', '#ckc-rate-form').once('ckcHodnoceniBehavior')
        .click(
          (ev) => {
            let inputName = 'order_1_1';
            let target = $(ev.target);
            let inputTarget = $('input[name="order_1_1"].form-text', '#ckc-rate-form');
            if (target.prop('checked')) {
              if (selectedValues.map.byInputValue[inputTarget.val()]) {
                unselectWork(inputTarget.val());
              }
              selectedValues.exclude_first_place = 1;
              selectedValues.map.byInputName[inputName].value = '';
              selectedValues.map.byInputName[inputName].valid = false;
              selectedValues.map.byInputName[inputName].extra = {disabled: true};
              inputTarget
                .val('')
                .prop('disabled', true)
                .parent()
                .addClass('form-disabled');
            } else {
              selectedValues.exclude_first_place = 0;
              selectedValues.map.byInputName[inputName].extra = {disabled: false};
              inputTarget
                .prop('disabled', false)
                .parent()
                .removeClass('form-disabled');
            }
            setInputInvalid(inputTarget, inputTarget.attr('name'), '', '');
          }
        );
    }

  };
})(jQuery, Drupal);
