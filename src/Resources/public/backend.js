window.addEventListener("load", function() {
	WEMPortfolio.makeListViewSortable(document.querySelector("#tl_listing tbody"));
});

/**
 * Provide methods to handle portfolio tasks.
 */
var WEMPortfolio =
{
	/**
	 * Make parent view items sortable
	 *
	 * @param {object} ul The DOM element
	 *
	 * @author Joe Ray Gregory
	 * @author Martin Auswöger
	 */
	makeListViewSortable: function(ul) {
		var ds = new Scroller(document.getElement('body'), {
			onChange: function(x, y) {
				this.element.scrollTo(this.element.getScroll().x, y);
			}
		});

		var list = new Sortables(ul, {
			constrain: true,
			opacity: 0.6,
			onStart: function() {
				ds.start();
			},
			onComplete: function() {
				ds.stop();
			},
			onSort: function(el) {

				var ul = el.getParent('ul'),
					wrapLevel = 0, divs, i;

				if (!ul) return;

				divs = ul.getChildren('li > div:first-child');

				if (!divs) return;

				for (i=0; i<divs.length; i++) {
					if (divs[i].hasClass('wrapper_stop') && wrapLevel > 0) {
						wrapLevel--;
					}

					divs[i].className = divs[i].className.replace(/(^|\s)indent[^\s]*/g, '');

					if (wrapLevel > 0) {
						divs[i].addClass('indent').addClass('indent_' + wrapLevel);
					}

					if (divs[i].hasClass('wrapper_start')) {
						wrapLevel++;
					}

					divs[i].removeClass('indent_first');
					divs[i].removeClass('indent_last');

					if (divs[i-1] && divs[i-1].hasClass('wrapper_start')) {
						divs[i].addClass('indent_first');
					}

					if (divs[i+1] && divs[i+1].hasClass('wrapper_stop')) {
						divs[i].addClass('indent_last');
					}
				}
			},
			handle: '.drag-handle'
		});

		list.active = false;

		list.addEvent('start', function() {
			list.active = true;
		});

		list.addEvent('complete', function(el) {
			if (!list.active) return;

			var elPrevious, posAfter, id, table;
			id = el.getChildren('td.tl_right_nowrap > .drag-handle > img')[0].get('data-item');
			table = el.getChildren('td.tl_right_nowrap > .drag-handle > img')[0].get('data-table');
			elPrevious = el.getPrevious('tr');

			if(!elPrevious) {
				posAfter = 0;
			} else {
				posAfter = elPrevious.getChildren('td.tl_right_nowrap > .drag-handle > img')[0].get('data-sorting');
			}

			new Request.Contao({ url: window.location.href }).post({'action':'WemPortfolioSortItems', 'id':id, 'posAfter':posAfter, 'table':table, 'REQUEST_TOKEN':Contao.request_token});
		});
	},
}