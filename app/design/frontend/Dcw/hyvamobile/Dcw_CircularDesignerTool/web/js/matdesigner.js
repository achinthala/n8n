/*jshint sub:true*/
/*jshint bitwise: true */

var canvasLength = 500;

if (scale === undefined) {
	var scale = 1; // window.outerWidth / canvasLength;
}

var containerWidth = window.outerWidth < 500 ? window.outerWidth : 500;
document.querySelector('canvas#a').style.width = (containerWidth - 21) + 'px';

// refresh canvas
function draw_a() {
	loadAllLogos().then(updateLogo);
	scaledContext = a_canvas.getContext('2d');
	scaledContext.save();
	scaledContext.scale(scale, scale);
	updateMatColor();
	updateInsideCircleColor();
	updateCircleColor();
	updateText();
	updateStartLines();
	updateSeams();
	scaledContext.restore();
	$('#matloading').removeClass('selected');

	//save data to input
	document.querySelector('#designerData').value = JSON.stringify({
		mat: mat,
		contact: contact
	});
}

function getUrlParameter(name) {
	name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
	var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
	var results = regex.exec(location.search);
	return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// eslint-disable-next-line no-extra-parens
('use strict');

var circleedittype = 'lines';
var lasttextpos = 1;
var lastlogopos = 5;
var temporientation = '';
var circleselection = {
	tencolor: 'none',
	tenthirtycolor: 'none',
	outsidecolor: 'none',
	color: '#FFFFFF',
	practicecolor: 'none'
};

var contact = {
	name: '',
	school: '',
	phone: '',
	email: '',
	state: '',
	zip: '',
	howfound: '',
	comments: '',
	contactid: ''
};

// New Mat Design default values
// if mat id isn't present, use these values, else grab values from database.
var mat = {
	update: false,
	size: 'a42x42',
	thickness: '1-5/8',
	color: '#0047c1',
	seamcolor: '#000000',
	fontfam: 'Arial',
	textcolor: '#ffffff',
	logocolor: '#ffffff',
	startlines: true,
	randomid: '',
	circles: {
		size: 30,
		tencolor: 'none',
		tenthirtycolor: 'none',
		outsidecolor: 'none',
		color: '#ffffff',
		practicecolor: 'none'
	},
	text: {
		values: new Array(9),
		color: new Array(9),
		orientation: new Array(9),
		font: new Array(9),
		outline: new Array(9)
	},
	logos: {
		logo: new Array(9),
		color: new Array(9),
		orientation: new Array(9),
		selection: new Array(9)
	}
};

//check for saved data
var loadedData = getUrlParameter('data');
if (loadedData) {
	loadedData = JSON.parse(atob(loadedData));
	mat = loadedData.mat;
	contact = loadedData.contact;
}

var a_context, circlefill, cxt, textCxt, textheight, textSplit, metrics, logoCxt, shortenedhoriztext;

var dim = {
	a36x36: {
		ystart: 0,
		radius1: 69,
		radius30: 194,
		radius28: 194,
		radius32: 194,
		seamstart: 84.5,
		seamseparation: 83,
		logosize: 75,
		logogap: 5,
		logogapy: 5,
		textpos: {
			t1: 0,
			t2: 0,
			t3: 0,
			t4: 0,
			t5: 0,
			t6: 0,
			t7: 0,
			t8: 0,
			t9: 0
		}
	},
	a42x38: {
		ystart: 24,
		radius1: 59.5,
		radius30: 178.6,
		radius28: 167,
		radius32: 190,
		seamstart: 71.5,
		seamseparation: 71.5,
		logosize: 60,
		logogap: 5,
		logogapy: 36,
		textpos: {
			t1: 0,
			t2: 0,
			t3: 0,
			t4: 0,
			t5: 0,
			t6: 0,
			t7: 0,
			t8: 0,
			t9: 0
		}
	},
	a42x40: {
		ystart: 12,
		radius1: 59.5,
		radius30: 178.6,
		radius28: 167,
		radius32: 190,
		seamstart: 71.5,
		seamseparation: 71.5,
		logosize: 60,
		logogap: 5,
		logogapy: 24,
		textpos: {
			t1: 0,
			t2: 0,
			t3: 0,
			t4: 0,
			t5: 0,
			t6: 0,
			t7: 0,
			t8: 0,
			t9: 0
		}
	},
	a42x42: {
		ystart: 0,
		radius1: 59.5,
		radius30: 178.6,
		radius28: 167,
		radius32: 190,
		seamstart: 71.5,
		seamseparation: 71.5,
		logosize: 60,
		logogap: 5,
		logogapy: 14.5,
		textpos: {
			t1: 0,
			t2: 0,
			t3: 0,
			t4: 0,
			t5: 0,
			t6: 0,
			t7: 0,
			t8: 0,
			t9: 0
		}
	}
};

var colorlist = {
	clighter: 'lighter',
	cdarker: 'darker',
	cffffff: 'white',
	cf9d700: 'athleticgold',
	cfffa00: 'yellow',
	cdfd576: 'vegas',
	cf8e4b0: 'manilla',
	cff9a00: 'orange',
	cff2600: 'brightred',
	cd81d00: 'scarlettred',
	c84221f: 'lightmaroon',
	c591b19: 'darkmaroon',
	c720086: 'purple',
	c21166d: 'navy',
	c0002f8: 'royal',
	c00c1ff: 'lightblue',
	c00cb00: 'kellygreen',
	c1d3e10: 'green',
	cdbdbdb: 'lightgray',
	c8c8c8c: 'darkgray',
	c8b4028: 'brown',
	c000000: 'black',
	cnone: 'none'
};

function deselect(hottype) {
	$('#' + hottype + 'deselect, #' + hottype + 'clearbutton, #' + hottype + 'rotatebutton').addClass('disabled');
	$('#' + hottype + 'hotspots div').removeClass('selected');
	$('#' + hottype + 'coverup').removeClass('hidden');
	$('.' + hottype + 'note:first').removeClass('hidden');
	$('.' + hottype + 'note:last').addClass('hidden');
	if (hottype === 'logo') {
		$('#currentlogoname').html('Current Logo: none');
		$('#logos a.selected').removeClass('selected');
		$('#currentlogo').html(
			'<img src="/static/frontend/Dcw/hyvamobile/en_US/images/logos/none.png" alt="current logo">'
		);
	} else {
		$('#lettering').prop('disabled', true);
		document.getElementById('lettering').value = 'Please select a text location';
	}
}

$(function() {
	// set form values for edit mode.
	$('#comments').val(contact.comments);
	$('input[name="name"]').val(contact.name);
	$('input[name="school"]').val(contact.school);
	$('input[name="zip"]').val(contact.zip);
	$('input[name="state"]').val(contact.state);
	$('input[name="phone"]').val(contact.phone);
	$('input[name="email"]').val(contact.email);
	// ===== custom code START =============
	if(contact.howfound) {
		$('select[name="howfound"]').val(contact.howfound); 
	}
	// ===== custom code END =============

	$('#loading')
		.hide() // hide it initially
		.ajaxStart(function() {
			$('#buttonbar').addClass('hidden');
			$(this).show();
		})
		.ajaxStop(function() {
			$(this).hide();
		});

	$('.hotspot').click(function() {
		if ($(this).hasClass('selected')) {
			// deselecting the currently-selected hotspot
			if (
				$(this)
					.parent()
					.hasClass('texthotspots')
			) {
				deselect('text');
			} else {
				deselect('logo');
			}
		} else {
			$(this)
				.siblings()
				.removeClass('selected');
			$(this).addClass('selected');
			if (
				$(this)
					.parent()
					.hasClass('texthotspots')
			) {
				document.getElementById('lettering').disabled = false;
				document.getElementById('lettering').focus();
				$('#textcoverup').addClass('hidden');
				$('.textnote:first').addClass('hidden');
				$('.textnote:last').removeClass('hidden');
			} else {
				$('#logocoverup').addClass('hidden');
				$('#logodeselect').removeClass('disabled');
				$('.logonote:first').addClass('hidden');
				$('.logonote:last').removeClass('hidden');
			}
		}
	});

	// clicking on mat background deselects hotspot
	$('.overlay').click(function(e) {
		if (e.target === this) {
			if ($(this).hasClass('texthotspots')) {
				deselect('text');
			} else {
				deselect('logo');
			}
		}
	});

	$('.paintbox, #logos a').click(function() {
		$(this)
			.parent()
			.children('.selected')
			.removeClass('selected');
		$(this).addClass('selected');
		if ($(this).hasClass('paintbox')) {
			$(this)
				.parent()
				.children('div')
				.children('.selected')
				.removeClass('selected');
		}
	});

	$('.switcher').click(function() {
		$('.switcher').removeClass('active');
		$(this).addClass('active');
		var propvalue = $(this).attr('id');

		var colorname = colorlist['c' + mat.circles[propvalue].replace('#', '')];
		$('#circlecolors .selected').removeClass('selected');

		if (typeof colorname === 'undefined') {
			$('#circlecolors input.color').addClass('selected');
		} else {
			$('.' + colorname).addClass('selected');
		}

		$('#switcherflash')
			.toggle()
			.fadeOut(400);
	});

	$('input.color').click(function() {
		$(this)
			.parent()
			.siblings('.selected')
			.removeClass('selected');
		$(this).addClass('selected');
	});

	$('#texthotspots, #logohotspots')
		.mouseenter(function() {
			$(this).removeClass('clear');
		})
		.mouseleave(function() {
			$(this).addClass('clear');
		});

	$('#fontselect, #logoselect').click(function() {
		$(this)
			.next()
			.toggleClass('hidden');
		if ($(this).attr('id') === 'logoselect') {
			loadAllLogos();
		}
	});

	$('#message, #messagebox a').click(function() {
		$('#message').fadeOut(function() {
			$(this).addClass('hidden');
		});
	});

	// ===== custom code =============
	/*popup.register({
		content: '.mat-submission-modal',
		name: 'mat-submission-modal',
		closeButton: false,
		suppressHash: true
	});*/
});

function loadAllLogos() {
	var functionPromise;

	if (loadAllLogos.calledOnce) {
		functionPromise = $.Deferred();
		functionPromise.resolve();
	} else {
		loadAllLogos.calledOnce = true;

		functionPromise = $.ajax({
			url: THEME_PATH+'/Dcw_CircularDesignerTool/wrestling-mat-designer-logos.html' // ===== custom code =============
		}).done(function(data) {
			$('#logos').html(data);
		});
	}

	return functionPromise.promise();
}

function overlay(which) {
	$('#' + which).toggleClass('clear');
}

function changeslide(num) {
	$('#nav a.active').removeClass('active');
	$('#nav a.nav' + num).addClass('active');
	// eslint-disable-next-line eqeqeq
	if (num == 3) {
		$('#texthotspots')
			.addClass('selected')
			.removeClass('clear');
	} else {
		$('#texthotspots').removeClass('selected');
	}
	// eslint-disable-next-line eqeqeq
	if (num == 4) {
		$('#logohotspots')
			.addClass('selected')
			.removeClass('clear');
		$('#navfinished').addClass('hidden');
	}
	// eslint-disable-next-line eqeqeq
	else if (num == 5) {
		$('#navfinished').removeClass('hidden');
		$('#logohotspots').removeClass('selected');
		$('body').addClass('final');
	} else {
		$('#logohotspots').removeClass('selected');
	}

	if (navigator.userAgent.match(/OS 5(_\d)+ like Mac OS X/i)) {
		$('#slidecontainer').css({
			marginLeft: '-' + (num - 1) * 360 + 'px'
		});
	} else {
		$('#slidecontainer').animate({ marginLeft: '-' + (num - 1) * 360 + 'px' }, 300);
	}
}

function updateMatColor() {
	var matsize = mat.size;
	a_context = a_canvas.getContext('2d');
	a_context.save();
	a_context.setTransform(1, 0, 0, 1, 0, 0);
	a_context.clearRect(0, 0, canvasLength, canvasLength);
	a_context.restore();
	a_context.fillStyle = mat.color;
	a_context.fillRect(0, 0 + dim[matsize].ystart, canvasLength, canvasLength - dim[matsize].ystart * 2);
}

function updateSeams() {
	var lines = a_canvas.getContext('2d');
	var matsize = mat.size;
	lines.beginPath();

	for (var x = dim[matsize].seamstart; x < canvasLength; x += dim[matsize].seamseparation) {
		lines.moveTo(x, 0 + dim[matsize].ystart);
		lines.lineTo(x, canvasLength - dim[matsize].ystart);
	}

	lines.lineWidth = 0.5;
	if (mat.color === '#000' || mat.color === '#000000') {
		mat.seamcolor = '#999999';
	} else {
		mat.seamcolor = '#000000';
	}
	lines.strokeStyle = mat.seamcolor;
	lines.stroke();
	lines.closePath();
	a_context.strokeStyle = '#000';
	a_context.lineWidth = 1.0;
	a_context.strokeRect(0, 0 + dim[matsize].ystart, canvasLength, canvasLength - dim[matsize].ystart * 2);
}

function updateStartLines() {
	if (mat.startlines === true || mat.startlines === 'true') {
		var startlines = a_canvas.getContext('2d');
		var matsize = mat.size;

		var lines = {
			a42x42: { x1: 231, x2: 267, y1: 250.5, y2: 262.5 },
			a42x40: { x1: 231, x2: 267, y1: 250.5, y2: 262.5 },
			a42x38: { x1: 231, x2: 267, y1: 250.5, y2: 262.5 },
			a36x36: { x1: 229, x2: 271, y1: 250.5, y2: 264.5 }
		};

		startlines.beginPath();
		startlines.moveTo(lines[matsize].x1, lines[matsize].y1);
		startlines.lineTo(lines[matsize].x2, lines[matsize].y1);
		startlines.moveTo(lines[matsize].x1, lines[matsize].y2);
		startlines.lineTo(lines[matsize].x2, lines[matsize].y2);
		startlines.lineWidth = 1;

		if (mat.circles.color === 'none') {
			startlines.strokeStyle = '#FFFFFF';
		} else {
			startlines.strokeStyle = mat.circles.color;
		}

		startlines.stroke();
		startlines.closePath();
		startlines.beginPath();
		startlines.moveTo(lines[matsize].x1, lines[matsize].y1);
		startlines.lineTo(lines[matsize].x1, lines[matsize].y2);
		startlines.strokeStyle = '#049704';
		startlines.stroke();
		startlines.closePath();
		startlines.beginPath();
		startlines.moveTo(lines[matsize].x2, lines[matsize].y1);
		startlines.lineTo(lines[matsize].x2, lines[matsize].y2);
		startlines.strokeStyle = '#ff0000';
		startlines.stroke();
		startlines.closePath();
	}
}

function updateCircleColor() {
	var radius2;
	var matsize = mat.size;
	radius2 = dim[matsize]['radius' + mat.circles.size];
	if (mat.circles.practicecolor !== 'none' && (matsize === 'a42x42' || matsize === 'a42x40')) {
		cxt = a_canvas.getContext('2d');
		cxt.lineWidth = 1;
		cxt.strokeStyle = mat.circles.practicecolor;
		var pleft = (canvasLength - dim[matsize].radius1 * 6) / 4 + dim[matsize].radius1;

		cxt.beginPath();
		cxt.arc(pleft, 250, dim[matsize].radius1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();

		cxt.beginPath();
		cxt.arc(canvasLength / 2, pleft, dim[matsize].radius1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();

		cxt.beginPath();
		cxt.arc(canvasLength - pleft, pleft, dim[matsize].radius1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();

		cxt.beginPath();
		cxt.arc(canvasLength - pleft, 250, dim[matsize].radius1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();

		cxt.beginPath();
		cxt.arc(pleft, pleft, dim[matsize].radius1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();

		cxt.beginPath();
		cxt.arc(pleft, canvasLength - pleft, dim[matsize].radius1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();

		cxt.beginPath();
		cxt.arc(250, canvasLength - pleft, dim[matsize].radius1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();

		cxt.beginPath();
		cxt.arc(canvasLength - pleft, canvasLength - pleft, dim[matsize].radius1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();
	}
	if (mat.circles.color === 'none') {
		cxt = a_canvas.getContext('2d');
		cxt.beginPath();
		cxt.arc(250, 250, 400, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();
	} else {
		matsize = mat.size;
		cxt = a_canvas.getContext('2d');
		cxt.lineWidth = 2;
		cxt.strokeStyle = mat.circles.color;
		cxt.beginPath();
		cxt.arc(250, 250, dim[matsize].radius1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();

		cxt.beginPath();
		cxt.arc(250, 250, radius2 - 1, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();

		cxt.beginPath();
		cxt.arc(250, 250, 400, 0, Math.PI * 2, true);
		cxt.closePath();
		cxt.stroke();
	}
}

function updateInsideCircleColor() {
	var matsize = mat.size;
	var radius2;
	radius2 = dim[matsize]['radius' + mat.circles.size];
	circlefill = a_canvas.getContext('2d');

	if (mat.circles.outsidecolor !== 'none') {
		circlefill.fillStyle = mat.circles.outsidecolor;
		circlefill.fillRect(0, 0 + dim[matsize].ystart, canvasLength, canvasLength - dim[matsize].ystart * 2);
		circlefill.beginPath();
		circlefill.arc(250, 250, radius2, 0, Math.PI * 2, true);
		circlefill.closePath();
		circlefill.fillStyle = mat.color;
		circlefill.fill();
	}
	if (mat.circles.tenthirtycolor !== 'none') {
		circlefill.beginPath();
		circlefill.arc(250, 250, radius2, 0, Math.PI * 2, true);
		circlefill.closePath();
		circlefill.fillStyle = mat.circles.tenthirtycolor;
		circlefill.fill();
		circlefill.beginPath();
		circlefill.arc(250, 250, dim[matsize].radius1, 0, Math.PI * 2, true);
		circlefill.closePath();
		circlefill.fillStyle = mat.color;
		circlefill.fill();
	}
	if (mat.circles.tencolor !== 'none') {
		circlefill.beginPath();
		circlefill.arc(250, 250, dim[matsize].radius1, 0, Math.PI * 2, true);
		circlefill.closePath();
		circlefill.fillStyle = mat.circles.tencolor;
		circlefill.fill();
	}

	circlefill.beginPath();
	circlefill.arc(250, 250, 400, 0, Math.PI * 2, true);
	circlefill.closePath();
	circlefill.fillStyle = mat.circles.tencolor;
	circlefill.stroke();
}

function maxText(text) {
	metrics = textCxt.measureText(text);
	if (metrics.width < 470) {
		shortenedhoriztext = text;
	} else {
		maxText(text.substr(0, text.length - 1));
	}
}

function updateText() {
	function setText(a, b, c, o) {
		if (mat.text.font[t] === 'Yearbook') {
			c = c - 6;
		}
		if (o === 'horiz') {
			if (mat.text.outline[t] === true || mat.text.outline[t] === 'true') {
				// outlined method
				// if safety-area is filled, use that color instead of mat color.
				if (mat.circles.outsidecolor !== 'none') {
					textCxt.fillStyle = mat.circles.outsidecolor;
				} else {
					textCxt.fillStyle = mat.color;
				}
				textCxt.fillText(a, b, c);
				textCxt.lineWidth = 1.5;
				textCxt.strokeStyle = mat.text.color[t];
				textCxt.strokeText(a, b, c);
			} else {
				// normal method
				textCxt.fillText(a, b, c);
			}
		} else {
			if (mat.text.outline[t] === true || mat.text.outline[t] === 'true') {
				// outlined method
				textCxt.fillStyle = mat.color;
				textCxt.fillText(a, b, c);
				textCxt.lineWidth = 1.5;
				textCxt.strokeStyle = mat.text.color[t];
				textCxt.strokeText(a, b, c);
			} else {
				// normal method
				textCxt.fillText(a, b, c);
			}
		}
	}

	// todo: if text is one character, change letter size to 5'?
	textCxt = a_canvas.getContext('2d');
	var matsize = mat.size;
	var m = 0;
	var fontlist = {
		Arial: { height: 0.735, family: 'Arial', weight: 'bold' },
		'Times New Roman': {
			height: 0.67,
			family: '"Times New Roman", Times',
			weight: 'bold'
		},
		'Roboto Slab': {
			height: 0.725,
			family: '"Roboto Slab"',
			weight: 'normal'
		},
		'Roboto Condensed': {
			height: 0.725,
			family: '"Roboto Condensed"',
			weight: 'normal'
		},
		Arimo: { height: 0.725, family: 'Arimo', weight: 'normal' },
		Graduate: { height: 0.9, family: 'Graduate', weight: 'normal' },
		Rockwell: {
			height: 0.725,
			family: 'RockwellStd-Bold',
			weight: 'normal'
		},
		Yearbook: { height: 0.895, family: 'YearbookSolid', weight: 'normal' },
		Princetown: {
			height: 0.8,
			family: 'PrincetownSHOP-Regular',
			weight: 'normal'
		},
		Machine: { height: 0.745, family: 'Machine', weight: 'normal' },
		Impact: { height: 0.795, family: 'Impact', weight: 'normal' },
		default: { height: 0.725, family: 'default', weight: 'normal' }
	};
	var spacing = {
		a36x36: {
			horizy: 43,
			vertx: 28,
			pixels: 28,
			starttextheight: 50,
			textheight: 45,
			botadjust: 20
		},
		a42x38: {
			horizy: 72,
			vertx: 36,
			pixels: 36,
			starttextheight: 76,
			textheight: 50,
			botadjust: 40
		},
		a42x40: {
			horizy: 60,
			vertx: 36,
			pixels: 36,
			starttextheight: 65.5,
			textheight: 50,
			botadjust: 30
		},
		a42x42: {
			horizy: 54,
			vertx: 36,
			pixels: 36,
			starttextheight: 54,
			textheight: 50,
			botadjust: 20
		}
	};

	if (typeof mat.text !== 'undefined') {
		for (var t in mat.text.values) {
			if (mat.text.values.hasOwnProperty(t)) {
				t = parseInt(t, 10);
				if (mat.text.values[t] && mat.text.values[t].length > 0) {
					var thisfont = mat.text.font[t];
					var fontWeight = '';
					textCxt.font = 'bold 50px Arial';
					var fontsize = fontlist.default.height;
					if (fontlist[thisfont] !== undefined) {
						fontsize = fontlist[thisfont].height;
						if (fontlist[thisfont].weight !== 'normal') {
							fontWeight = fontlist[thisfont].weight + ' ';
						}
						thisfont = fontlist[thisfont].family;
					}
					textCxt.font = fontWeight + spacing[matsize].pixels / fontsize + 'px ' + thisfont;
					textCxt.fillStyle = mat.text.color[t];

					if (t === 0) {
						// Top Left - Horizontal
						// eslint-disable-next-line eqeqeq
						if (mat.text.orientation[t] == 1) {
							textCxt.textAlign = 'left';
							maxText(mat.text.values[t]);
							shortenedhoriztext = mat.text.values[t];
							setText(shortenedhoriztext, 20, spacing[matsize].horizy, 'horiz');
						}
						// Top Left - Vertical
						else {
							textCxt.textAlign = 'center';
							textheight = spacing[matsize].horizy;
							textSplit = mat.text.values[t].substr(0, 9).split('');
							for (m = 0; m < textSplit.length; m++) {
								setText(textSplit[m], spacing[matsize].vertx, textheight, 'vert');
								textheight = textheight + spacing[matsize].textheight;
							}
						}
					}

					if (t === 1) {
						textCxt.textAlign = 'center';
						maxText(mat.text.values[t]);
						setText(shortenedhoriztext, 250, spacing[matsize].horizy, 'horiz');
					}
					if (t === 2) {
						// eslint-disable-next-line eqeqeq
						if (mat.text.orientation[t] == 1) {
							textCxt.textAlign = 'right';
							maxText(mat.text.values[t]);
							setText(shortenedhoriztext, 480, spacing[matsize].horizy, 'horiz');
						} else {
							textCxt.textAlign = 'center';
							textheight = spacing[matsize].horizy;
							textSplit = mat.text.values[t].substr(0, 9).split('');
							for (m = 0; m < textSplit.length; m++) {
								setText(textSplit[m], canvasLength - spacing[matsize].vertx, textheight, 'vert');
								textheight = textheight + spacing[matsize].textheight;
							}
						}
					}
					if (t === 3) {
						textCxt.textAlign = 'center';
						textSplit = mat.text.values[t].substr(0, 9).split('');
						// eslint-disable-next-line no-extra-parens
						textheight = 250 - (textSplit.length * (spacing[matsize].textheight - 2)) / 2 + spacing[matsize].vertx;
						for (m = 0; m < textSplit.length; m++) {
							setText(textSplit[m], spacing[matsize].vertx, textheight, 'horiz');
							textheight = textheight + spacing[matsize].textheight;
						}
					}
					if (t === 4) {
						textCxt.textAlign = 'center';
						maxText(mat.text.values[t]);
						setText(shortenedhoriztext, 250, 250 + spacing[matsize].pixels / 2, 'horiz');
					}
					if (t === 5) {
						textCxt.textAlign = 'center';
						textSplit = mat.text.values[t].substr(0, 9).split('');
						// eslint-disable-next-line no-extra-parens
						textheight = 250 - (textSplit.length * (spacing[matsize].textheight - 2)) / 2 + spacing[matsize].vertx;
						for (m = 0; m < textSplit.length; m++) {
							setText(textSplit[m], canvasLength - spacing[matsize].vertx, textheight, 'vert');
							textheight = textheight + spacing[matsize].textheight;
						}
					}
					if (t === 6) {
						// eslint-disable-next-line eqeqeq
						if (mat.text.orientation[t] == 1) {
							textCxt.textAlign = 'left';
							maxText(mat.text.values[t]);
							setText(shortenedhoriztext, 20, canvasLength - spacing[matsize].horizy + spacing[matsize].pixels, 'horiz');
						} else {
							textCxt.textAlign = 'center';
							textSplit = mat.text.values[t].substr(0, 9).split('');
							textheight = canvasLength - (textSplit.length - 1) * spacing[matsize].textheight - spacing[matsize].botadjust;
							for (m = 0; m < textSplit.length; m++) {
								setText(textSplit[m], spacing[matsize].vertx, textheight, 'vert');
								textheight = textheight + spacing[matsize].textheight;
							}
						}
					}
					if (t === 7) {
						textCxt.textAlign = 'center';
						maxText(mat.text.values[t]);
						setText(shortenedhoriztext, 250, canvasLength - spacing[matsize].horizy + spacing[matsize].pixels, 'horiz');
					}
					if (t === 8) {
						// eslint-disable-next-line eqeqeq
						if (mat.text.orientation[t] == 1) {
							textCxt.textAlign = 'right';
							maxText(mat.text.values[t]);
							setText(shortenedhoriztext, 480, canvasLength - spacing[matsize].horizy + spacing[matsize].pixels, 'horiz');
						} else {
							textCxt.textAlign = 'center';
							textSplit = mat.text.values[t].substr(0, 9).split('');
							textheight = canvasLength - (textSplit.length - 1) * spacing[matsize].textheight - spacing[matsize].botadjust;
							for (m = 0; m < textSplit.length; m++) {
								setText(textSplit[m], canvasLength - spacing[matsize].vertx, textheight, 'vert');
								textheight = textheight + spacing[matsize].textheight;
							}
						}
					}
				}
			}
		}
	}
}

function formText() {
	// Messy code. Sets default values to avoid later 'undefined' errors
	if (parseInt(mat.text.orientation[lasttextpos - 1], 10) !== 0 && parseInt(mat.text.orientation[lasttextpos - 1]) !== 1) {
		mat.text.orientation[lasttextpos - 1] = 0;
		mat.text.outline[lasttextpos - 1] = '';
	}
	var texttoadd = document.getElementById('lettering').value;
	addText(texttoadd, lasttextpos, mat.textcolor);
	$('#textclearbutton').removeClass('disabled');
	// eslint-disable-next-line eqeqeq
	if (lasttextpos == 1 || lasttextpos == 3 || lasttextpos == 7 || lasttextpos == 9) {
		$('#textrotatebutton').removeClass('disabled');
	}
	if (texttoadd === '') {
		$('#textclearbutton').addClass('disabled');
		$('#textrotatebutton').addClass('disabled');
	}
}

function loadText(position) {
	lasttextpos = position;
	var inputtext = mat.text.values[position - 1];
	if ((position === 1 || position === 3 || position === 7 || position === 9) && inputtext !== '') {
		$('#textrotatebutton').removeClass('disabled');
	} else {
		$('#textrotatebutton').addClass('disabled');
	}

	if (inputtext !== '' && typeof inputtext !== 'undefined') {
		mat.fontfam = mat.text.font[position - 1];
		mat.textcolor = mat.text.color[position - 1];
		document.getElementById('lettering').value = inputtext;
		$('#textclearbutton').removeClass('disabled');
		if (mat.text.outline[position - 1] === 'true' || mat.text.outline[position - 1] === true) {
			document.getElementById('textoutline').checked = true;
		} else {
			document.getElementById('textoutline').checked = false;
		}
		$('#letteringcolors a.selected, #letteringcolors input.selected').removeClass('selected');
		var colorname = colorlist['c' + mat.text.color[position - 1].replace('#', '')];
		if (colorname) {
			$('#letteringcolors a.' + colorname).addClass('selected');
		} else {
			$('#letteringcolors input').addClass('selected');
		}
		var fontname = mat.text.font[position - 1];
		$('#fontselect')
			.html(
				'Current Font: <span class="' + fontname.toLowerCase().replace(/ /g, '') + '">' + fontname.toUpperCase() + '</span>'
			)
			.removeClass('selected');
	} else {
		document.getElementById('lettering').value = '';
		$('#textclearbutton').addClass('disabled');
		document.getElementById('textoutline').checked = false;
	}
}

function clearText() {
	document.getElementById('lettering').value = '';
	addText('', lasttextpos);
	$('#textclearbutton, #textrotatebutton').addClass('disabled');
	document.getElementById('lettering').focus();
}

function updateLogo() {
	var imagePromises = [];
	var loadedImages = [];

	// logosize = 60 or 75
	if (typeof mat.logos !== 'undefined') {
		for (var t in mat.logos.logo) {
			// eslint-disable-next-line eqeqeq
			if (mat.logos.logo[t] == 1) {
				var imgPromise = $.Deferred();
				imagePromises.push(imgPromise);

				var logoimg = document.getElementById(mat.logos.selection[t]);

				var imageForCanvas = new Image();
				imageForCanvas.crossOrigin = 'Anonymous';
				imageForCanvas.src = logoimg.src;

				(function(loop, promise, i) {
					i.onload = function() {
						loadedImages[loop] = i;
						promise.resolve();
					};
				})(t, imgPromise, imageForCanvas);
			}
		}

		$.when.apply($, imagePromises).then(function() {
			logoCxt = a_canvas.getContext('2d');
			var matsize = mat.size;
			var logosize = dim[matsize].logosize;
			var logopos = {
				1: {
					x: dim[matsize].logogap + logosize / 2,
					y: dim[matsize].logogapy + logosize / 2
				},
				3: {
					x: canvasLength - logosize / 2 - dim[matsize].logogap,
					y: dim[matsize].logogapy + logosize / 2
				},
				5: { x: 250, y: 250 },
				7: {
					x: dim[matsize].logogap + logosize / 2,
					y: canvasLength - logosize / 2 - dim[matsize].logogapy
				},
				9: {
					x: canvasLength - logosize / 2 - dim[matsize].logogap,
					y: canvasLength - logosize / 2 - dim[matsize].logogapy
				}
			};

			for (var t in mat.logos.logo) {
				// eslint-disable-next-line eqeqeq
				if (mat.logos.logo[t] == 1) {
					var rotation = mat.logos.orientation[t];
					var logoimg = document.getElementById(mat.logos.selection[t]);

					var logo_context = a_canvas.getContext('2d');
					logo_context.save();
					logo_context.translate(logopos[Number(t) + 1].x, logopos[Number(t) + 1].y);
					// eslint-disable-next-line no-extra-parens
					logo_context.rotate((90 * rotation * Math.PI) / 180);

					var tempCanvas = document.createElement('canvas');
					tempCanvas.width = logoimg.naturalWidth;
					tempCanvas.height = logoimg.naturalHeight;

					var tempContext = tempCanvas.getContext('2d');
					tempContext.drawImage(loadedImages[t], 0, 0);
					var rgb = hexToRgb(mat.logos.color[t]);
					var imageData = tempContext.getImageData(0, 0, logoimg.naturalWidth, logoimg.naturalHeight);
					for (var i = 0; i < imageData.data.length; i += 4) {
						imageData.data[i] = rgb['r'];
						imageData.data[i + 1] = rgb['g'];
						imageData.data[i + 2] = rgb['b'];
					}
					tempContext.putImageData(imageData, 0, 0);
					logo_context.drawImage(tempCanvas, -logosize / 2, -logosize / 2, logosize, logosize);
					logo_context.restore();
				}
			}
		});
	}
}

function hexToRgb(hex) {
	// Expand shorthand form (e.g. '03F') to full form (e.g. '0033FF')
	var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
	hex = hex.replace(shorthandRegex, function(m, r, g, b) {
		return r + r + g + g + b + b;
	});

	var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
	return result
		? {
				r: parseInt(result[1], 16),
				g: parseInt(result[2], 16),
				b: parseInt(result[3], 16)
		  }
		: null;
}

/* ************** */
/* SETTING VALUES */
/* ************** */

function setSizing(size) {
	mat.size = size;
	// if 36x36 or 42x38, change to 28' circle and disable 30 & 32 options. if any other size, make sure 30 & 32 are enabled.
	if (mat.size === 'a36x36' || mat.size === 'a42x38') {
		$('#circlesize28').prop('checked', true);
		mat.circles.size = 28;
		$('.switcher.active').removeClass('active');
		setcircletype('lines');
		$('.circlelines').addClass('active');
		circleedittype = 'lines';
		mat.circles.practicecolor = 'none';
		$('.circlesize30, .circlesize32, .circlepractice').addClass('disabled');
		$('#circlesize30, #circlesize32').prop('disabled', true);
		if (mat.size === 'a42x38') {
			$('#texthotspots .bottom, #logohotspots .bottom').css('bottom', '28px');
			$('#texthotspots .top, #logohotspots .top').css('top', '28px');
		} else {
			$('#texthotspots .bottom, #logohotspots .bottom').css('bottom', '4px');
			$('#texthotspots .top, #logohotspots .top').css('top', '4px');
		}
	} else if (mat.size === 'a42x40') {
		$('#circlesize30').prop({ checked: true, disabled: false });
		$('.circlesize30').removeClass('disabled');
		mat.circles.size = 30;
		$('.circlesize32').addClass('disabled');
		$('#circlesize32').prop('disabled', true);
		$('#texthotspots .bottom, #logohotspots .bottom').css('bottom', '17px');
		$('#texthotspots .top, #logohotspots .top').css('top', '17px');
	} else {
		$('#circlesize30, #circlesize32, #circlepractice').prop('disabled', false);
		$('.circlesize30, .circlesize32, .circlepractice').removeClass('disabled');
		$('#circlesize30').prop('checked', true);
		mat.circles.size = 30;
		$('#texthotspots .bottom, #logohotspots .bottom').css('bottom', '10px');
		$('#texthotspots .top, #logohotspots .top').css('top', '10px');
	}
	draw_a();
}

function setstartlines(v) {
	mat.startlines = v;
	draw_a();
}

function settextoutline(v) {
	mat.text.outline[lasttextpos - 1] = v;
	draw_a();
}

function setcirclesize(size) {
	mat.circles.size = Number(size);
	draw_a();
}

function changeMatColor(color) {
	mat.color = color;
	draw_a();
}

function changeSeams(color) {
	mat.seamcolor = color;
	draw_a();
}

function setcircletype(type) {
	circleedittype = type;
	if (type === 'practice') {
		$('.pcircles').removeClass('hidden');
	} else {
		$('.pcircles').addClass('hidden');
	}
}

function shadeColor(color, percent) {
	var num = parseInt(color.slice(1), 16),
		amt = Math.round(2.55 * percent),
		R = (num >> 16) + amt,
		// eslint-disable-next-line no-extra-parens
		B = ((num >> 8) & 0x00ff) + amt,
		G = (num & 0x0000ff) + amt;
	return (
		'#' +
		(
			0x1000000 +
			// eslint-disable-next-line no-extra-parens
			(R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
			// eslint-disable-next-line no-extra-parens
			(B < 255 ? (B < 1 ? 0 : B) : 255) * 0x100 +
			// eslint-disable-next-line no-extra-parens
			(G < 255 ? (G < 1 ? 0 : G) : 255)
		)
			.toString(16)
			.slice(1)
	);
}

function editcircle(color) {
	if (circleedittype === 'lines') {
		mat.circles.color = color;
	} else if (circleedittype === 'ten') {
		mat.circles.tencolor = color;
	} else if (circleedittype === 'tenthirty') {
		mat.circles.tenthirtycolor = color;
	} else if (circleedittype === 'outside') {
		mat.circles.outsidecolor = color;
	} else if (circleedittype === 'practice') {
		if (color === 'lighter') {
			mat.circles.practicecolor = shadeColor(mat.color, 15);
		} else if (color === 'darker') {
			mat.circles.practicecolor = shadeColor(mat.color, -15);
		} else {
			mat.circles.practicecolor = color;
		}
	}
	draw_a();
}

function updateTextColor(color) {
	mat.text.color[lasttextpos - 1] = color;
	mat.textcolor = color;
	draw_a();
}

function rotateText() {
	// eslint-disable-next-line eqeqeq
	if (mat.text.orientation[lasttextpos - 1] == 0) {
		mat.text.orientation[lasttextpos - 1] = 1;
	} else {
		mat.text.orientation[lasttextpos - 1] = 0;
	}

	draw_a();
}

function addText(text, pos, color) {
	lasttextpos = pos;
	if (!color) {
		color = mat.textcolor;
	}
	text = text.replace(/[^a-zA-Z \.0-9-]+/g, '');
	text = text.toUpperCase();
	pos = pos - 1;
	mat.text.values[pos] = text;
	mat.text.color[pos] = color;
	mat.text.font[pos] = mat.fontfam;
	mat.text.orientation[pos] = 0;
	draw_a();
}

function loadLogo(position) {
	lastlogopos = position;
	if (mat.logos.logo[position - 1] === 0 || typeof mat.logos.logo[position - 1] === 'undefined') {
		$('#logorotatebutton, #logoclearbutton').addClass('disabled');
		$('#logos a.selected').removeClass('selected');
		$('#currentlogoname').html('Current Logo: none');
		$('#currentlogo').html(
			'<img src="/static/frontend/Dcw/hyvamobile/en_US/images/logos/none.png" alt="current logo">'
		);
	} else if (typeof mat.logos.selection[position - 1] !== 'undefined') {
		$('#logorotatebutton, #logoclearbutton').removeClass('disabled');
		var lname = mat.logos.selection[position - 1];
		$('#currentlogoname')
			.html('Current Logo: ' + lname.substr(0, 1).toUpperCase() + lname.substr(1))
			.removeClass('selected');
		$('#currentlogo').html(
			'<img src="/static/frontend/Dcw/hyvamobile/en_US/images/logos/' +
				lname +
				'.png" alt="current logo">'
		);
		$('#' + lname)
			.parent()
			.addClass('selected');
		$('#logos a.selected, #logocolors a.selected, #logocolors input.selected').removeClass('selected');
		var colorname = colorlist['c' + mat.logos.color[position - 1].replace('#', '')];
		if (colorname) {
			$('#logocolors a.' + colorname).addClass('selected');
		} else {
			$('#logocolors input').addClass('selected');
		}
	}
}

function clearLogo() {
	mat.logos.logo[lastlogopos - 1] = 0;
	draw_a();
	$('#logoclearbutton, #logorotatebutton').addClass('disabled');
	$('#currentlogo').html(
		'<img src="/static/frontend/Dcw/hyvamobile/en_US/images/logos/none.png" alt="current logo">'
	);
	$('#currentlogoname').html('Current Logo: none');
	$('#logos a.selected').removeClass('selected');
}

function selectLogo(name) {
	mat.logos.selection[lastlogopos - 1] = name;
	mat.logos.color[lastlogopos - 1] = mat.logocolor;
	if (typeof mat.logos.orientation[lastlogopos - 1] === 'undefined') {
		mat.logos.orientation[lastlogopos - 1] = 0;
	}
	if (name !== 'none') {
		mat.logos.logo[lastlogopos - 1] = 1;
	}
	draw_a();
	$('#logoclearbutton, #logorotatebutton').removeClass('disabled');
	$('#currentlogoname')
		.html('Current Logo: ' + name.substr(0, 1).toUpperCase() + name.substr(1))
		.removeClass('selected');
	$('#currentlogo').html(
		'<img src="/static/frontend/Dcw/hyvamobile/en_US/images/logos/' + name + '.png" alt="current logo">'
	);
	$('#logolist').toggleClass('hidden');
}

function rotateLogo() {
	temporientation = mat.logos.orientation[lastlogopos - 1];
	mat.logos.orientation[lastlogopos - 1] = (mat.logos.orientation[lastlogopos - 1] + 1) % 4;
	draw_a();
}

function changeLogoColor(color) {
	mat.logocolor = color;
	mat.logos.color[lastlogopos - 1] = color;
	draw_a();
}

function updateFont(font) {
	// disable outlined text option if font = princetown. make sure to uncheck/disable outlined text if it's already checked. id: #textoutline, function: settextoutline
	if (font === 'Princetown') {
		$('#textoutline').prop({ disabled: true, checked: false });
		$('label[for=textoutline]').addClass('disabled');
		settextoutline(false);
	} else {
		$('#textoutline').prop('disabled', false);
		$('label[for=textoutline]').removeClass('disabled');
	}
	mat.fontfam = font;
	mat.text.font[lasttextpos - 1] = font;
	draw_a();
	$('#fontselect')
		.html('Current Font: <span class="' + font.toLowerCase().replace(/ /g, '') + '">' + font.toUpperCase() + '</span>')
		.removeClass('selected');
	$('#fontlist').toggleClass('hidden');
}

function exporttoimage() {
	window.location = a_canvas.toDataURL('image/png');
}

function zoom() {
	if ($('.fulloverlay').hasClass('hidden')) {
		var winheight = $(window).height();
		if (winheight > $(window).width()) {
			winheight = $(window).width();
		}
		if (winheight > 1000) {
			winheight = 1000;
		}
		$('.fulloverlay').removeClass('hidden');
		$('.fulloverlay').click(function() {
			scale = 1;
			a_canvas.width = canvasLength;
			a_canvas.height = canvasLength;
			$('canvas#a')
				.removeClass('zoom')
				.css('margin', '0');
			$('.fulloverlay').addClass('hidden');
			draw_a();
		});
		$('canvas#a').addClass('zoom');
		// set margins for centering
		scale = winheight / canvasLength;
		a_canvas.width = winheight;
		a_canvas.height = winheight;
		$('canvas#a').css({
			'margin-left': 0 - +(winheight / 2),
			'margin-top': 0 - winheight / 2
		});
	} else {
		scale = 1;
		a_canvas.width = canvasLength;
		a_canvas.height = canvasLength;
		$('canvas#a')
			.removeClass('zoom')
			.css('margin', '0');
		$('.fulloverlay').addClass('hidden');
	}
	draw_a();
}

function exitviewmode() {
	$('#navviewmode').fadeToggle('fast');
	$('#propview').fadeToggle('fast', function() {
		$('#propsize').fadeToggle('fast');
	});
}

function iphone_portraitmode() {
	var winwidth = $(window).width();

	if (winwidth >= 320 || winwidth <= 400) {
		$('body').addClass('mobile portrait');
		scale = winwidth / canvasLength;
		a_canvas.width = winwidth;
		a_canvas.height = winwidth;
		$('#drawing div').css({ width: 'auto', height: 'auto' });
	}

	draw_a();
}
