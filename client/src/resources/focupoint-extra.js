(function ($) {

	$.entwine('ss', function ($) {

    $('.cms-edit-form').entwine({
      onadd: function() {

        $('.image-coord-field .grid').each(function (i, e) {

          if ($(e).closest('.ui-accordion').length)
          {
            $(e).closest('.ui-accordion').find('.ui-accordion-header').click(() => {
              $(this).updateGrid();
            })
          }
        });

        this._super();
      },
      onchange: function() {
        $('.uploadfield-item__remove-btn').off('click').click((e) => {

          let imageHiddenField = $(e.currentTarget).closest('.entwine-uploadfield.uploadfield').find('input');

          if (imageHiddenField.length) {

            let fieldName = imageHiddenField.attr('name').split('[')[0];

            let focusPointXField = $('input[name="' + fieldName + '-_1_-FocusPointX"]');

            if (focusPointXField.length) {
              focusPointXField.closest('.CompositeField.togglecomposite').remove()
            }
          }

        })
			}
    });


    // console.log('init')

    // $('.col-FocusPointX').remove();
    // $('.col-FocusPointY').remove();

		function handleResize() {
			// Call the updateGrid function when the window is resized
			$('.image-coord-field .grid').each(function () {
				$(this).updateGrid();
			});
		}

        // Bind the handleResize function to the window's resize event
        $(window).on('resize', handleResize);

		$('.image-coord-field .grid').entwine({

			onmatch: function () {
				var $this = this; // Store a reference to the current element

    			setTimeout(function () {

        			$this.updateGrid(); // Call updateGrid after a 1-second delay

    			}, 200);
			},
			getCoordField: function (axis) {
				var fieldName = (axis.toUpperCase() === 'Y') ? this.data('yFieldName') : this.data('xFieldName');
				var fieldSelector = "input[name='" + fieldName + "']";
        // console.log('LOOK FOR:', fieldName, this)
				return this.closest('.image-coord-fieldgroup').find(fieldSelector);
			},
			roundXYValues: function (XYval) {
				return XYval.toFixed(4);
			},
			updateGrid: function () {

        let inGridItem = $('.ss-gridfield-item .image-coord-field');

        if (inGridItem.length) {
          inGridItem.closest('td').addClass('imagecoord image-coord-fieldgroup')
        }

				var grid = $(this);

        // Get coordinates from text fields
				var focusX = grid.getCoordField('x').val();
				var focusY = grid.getCoordField('y').val();
        // console.log('CORDS:', focusX, focusY)

				// Calculate background positions
				var backgroundWH = 11; // Width and height of grid background image
				var bgOffset = Math.floor(-backgroundWH / 2);
				var fieldW = grid.width();
				var fieldH = grid.height();
				var leftBG = this.data('cssGrid') ? bgOffset + (focusX * fieldW) : bgOffset + ((focusX / 2 + .5) * fieldW);
				var topBG = this.data('cssGrid') ? bgOffset + (focusY * fieldH) : bgOffset + ((-focusY / 2 + .5) * fieldH);
        // console.log(leftBG,  topBG)
				// Line up crosshairs with click position
				grid.css('background-position', leftBG + 'px ' + topBG + 'px');

        // update fpaim
        grid.find('.fpaim').css({'left' :  Math.round((Number(focusX) + 1) * 0.5 * fieldW), 'top': Math.round((Number(focusY) + 1) * 0.5 * fieldH), width: fieldW * 2, height: fieldH * 2});

			},
			onclick: function (e) {
				var grid = $(this);
				var fieldW = grid.width();
				var fieldH = grid.height();

				// Calculate ImageCoord coordinates
				var offsetX = e.pageX - grid.offset().left;
				var offsetY = e.pageY - grid.offset().top;
        // console.log('OFFSET:', offsetX, offsetY)
				var focusX = this.data('cssGrid') ? offsetX / fieldW : (offsetX / fieldW - .5) * 2;
				var focusY = this.data('cssGrid') ? offsetY / fieldH : (offsetY / fieldH - .5) * 2;

				// Pass coordinates to form fields
        // console.log('click', focusX , focusY)
				this.getCoordField('x').val(focusX)//.val(this.roundXYValues(focusX));
				this.getCoordField('y').val(focusY)//.val(this.roundXYValues(focusY));
        // console.log('check', this.getCoordField('x').val(), this.getCoordField('y').val())
				// Update focus point grid
				this.updateGrid();
				$(this).closest('form').addClass('changed');
			},
            onmousemove: function (e) {
                var grid = $(this);
                var fieldW = grid.width();
                var fieldH = grid.height();

                // Calculate ImageCoord coordinates based on mouse position
                var offsetX = e.pageX - grid.offset().left;
                var offsetY = e.pageY - grid.offset().top;
                var focusX = this.data('cssGrid') ? offsetX / fieldW : (offsetX / fieldW - .5) * 2;
                var focusY = this.data('cssGrid') ? offsetY / fieldH : (offsetY / fieldH - .5) * -2;

				var xysumfield = this.closest('.image-coord-fieldgroup').find(".sumField");
				xysumfield.html('mouseX ' + this.roundXYValues(focusX) + ' / mouseY ' + this.roundXYValues(focusY));
            }

		});
	});


}(jQuery));