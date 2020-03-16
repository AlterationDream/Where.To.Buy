<script type="text/javascript">
	var cache ={}
	function rowTemplate(str, data){
    // Выяснить, мы получаем шаблон или нам нужно его загрузить
    // обязательно закешировать результат
    var fn = !/\W/.test(str) ?
    cache[str] = cache[str] ||
    rowTemplate(document.getElementById(str).innerHTML) :

      // Сгенерировать (и закешировать) функцию, 
      // которая будет служить генератором шаблонов
      new Function("obj",
      	"var p=[],print=function(){p.push.apply(p,arguments);};" +
      "with(obj){p.push('" +
        str
        .replace(/[\r\t\n]/g, " ")
        .split("<%").join("\t")
        .replace(/((^|%>)[^\t]*)'/g, "$1\r")
        .replace(/\t=(.*?)%>/g, "',$1,'")
        .split("\t").join("');")
        .split("%>").join("p.push('")
        .split("\r").join("\\'")
        + "');}return p.join('');");
    return data ? fn( data ) : fn;
}
$(document).on('change', 'select[data-mode]', function(){
	var value = $(this).val();
	if(!value)value=$(this).attr('data-value');
	if(!value)value=$(this).find('option').first().attr('value');
	getOptions($(this).attr('data-mode'), value, $(this).closest('[data-id]').length>0?$(this).closest('[data-id]'):$(this).closest('table'));
})
$(document).ready(function(){
	$('tr[data-id]').each(function(){
		initRow($(this));
	})
})
function initRow(row){
	var input = row.find('.input-row');
	initFilterConditionsControl({
		oCont: input.closest('td')[0],
		oInput: input[0],
		propertyID: row.attr('data-id'),
	})
}
function removeRow(input){
	$(input).closest('tr').remove()
}
function addRow(){
	var row = $(rowTemplate($('#row-template').html(), {ID: 'n'+BX.util.getRandomString(5)}));
	console.log(row)
	$('.last-row').before(row);
	initRow(row);
}
function setOptions(mode, value, parent){
	$.ajax({
		data: {
			ajax_refresh: true,
			ajax_name: value,
			ajax_mode: mode,
		},
		success: function(html){
			parent.find('select[data-mode="'+mode+'"]').html(html)
		}
	})
}
function getOptions(type, value, parent){
	var selects = {
		'Region': ['City' ,'Subway', 'District'],
		'City': ['Subway', 'District'],
		'Category': ['TypeId'],
	}, 
	select = selects[type];
	if(!value && type=='City'){
		var alt=$('select[data-mode="Region"]');
		value = alt.val();
		if(!value)value=alt.attr('data-value');
		if(!value)value=alt.find('option').first().attr('value');
	}
	if(select){
		for(var i in select){
			var mode = select[i];
			setOptions(mode, value, parent);
		}

	}
}
function initFilterConditionsControl(params)
{
	var data = {'iblockId': 2};
	console.log(params);
	if (data)
	{
		window['filter_conditions_' + params.propertyID] = new FilterConditionsParameterControl(data, params);
	}
}

function FilterConditionsParameterControl(data, params)
{
	var rand = BX.util.getRandomString(5),
	that = this;

	this.params = params || {};
	this.message = {invalid: ''};
	this.data = data || {};
	this.nodes = {};
	this.ids = {
		form: 'limit_cond_form_' + this.params.propertyID + '_' + rand,
		container: 'limit_cond_container_' + this.params.propertyID + '_' + rand,
		treeObject: 'limit_cond_obj_' + this.params.propertyID + '_' + rand
	};

	this.buildNodes();

	BX.addCustomEvent('onTreeConditionsInit', BX.proxy(this.modifyTreeParams, this));
	BX.addCustomEvent('onAdminTabsDeleteLevel', BX.proxy(this.onChangeForm, this));
	BX.addCustomEvent('onNextVisualChange', BX.proxy(this.onChangeForm, this));
	BX.addCustomEvent('onTreeCondPopupClose', BX.proxy(this.onChangeForm, this));
	BX.addCustomEvent('onBeforeWindowClose', BX.proxy(this.onChangeForm, this));
	BX.addCustomEvent('onMenuItemSelected', BX.proxy(this.onChangeForm, this));
	BX.addCustomEvent('onWindowClose', BX.proxy(this.onChangeForm, this));

	BX.loadScript('/bitrix/js/catalog/core_tree.js', function(){
		BX.ajax({
			timeout: 60,
			method: 'POST',
			dataType: 'html',
			url: '?ajax_tree=1',
			data: {
				action: 'init',
				condition: that.params.oInput.value,
				ids: that.ids,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.proxy(this.saveData, this)
		})
	});
	BX.loadCSS('/bitrix/panel/catalog/catalog_cond.css');
	// BX.loadCSS(this.path + '/style.css?' + rand);
}

FilterConditionsParameterControl.prototype =
{
	
	deleteFromArray: function(keys, array)
	{
		if (!BX.type.isArray(keys) || !BX.type.isArray(array))
			return;

		for (var i = array.length; --i;)
		{
			if (!!array[i] && array.hasOwnProperty(i))
			{
				if (BX.util.in_array(i, keys))
				{
					array.splice(i, 1);
				}
			}
		}
	},

	onChangeForm: function()
	{
		if (!this.nodes.form)
			return;

		BX.fireEvent(this.nodes.form, 'change');
	},

	modifyTreeParams: function(arParams, obTree, obControls)
	{
		if (!obControls)
			return;

		var i, control, keysToDelete = [];

		for (i in obControls)
		{
			if (obControls.hasOwnProperty(i))
			{
				control = obControls[i];
				if (control.group)
				{
					this.modifyCondGroup(control);
				}
				else
				{
					if (this.modifyCondValueGroup(control))
					{
						keysToDelete.push(i);
					}
				}
			}
		}

		this.deleteFromArray(keysToDelete, obControls);
	},

	modifyCondGroup: function(ctrl)
	{
		var k;

		if (ctrl.visual)
		{
			for (k in ctrl.visual.values)
			{
				if (ctrl.visual.values.hasOwnProperty(k))
				{
					if (ctrl.visual.values[k].True === 'False')
					{
						ctrl.visual.values.splice(k, 1);
						ctrl.visual.logic.splice(k, 1);
					}
				}
			}
		}

		if (ctrl.control)
		{
			for (k in ctrl.control)
			{
				if (ctrl.control.hasOwnProperty(k))
				{
					ctrl.control[k].dontShowFirstOption = true;

					if (ctrl.control[k].id === 'True')
					{
						delete ctrl.control[k].values.False;
					}
				}
			}
		}
	},

	modifyCondValueGroup: function(ctrl)
	{

		
		return false;
	},

	buildNodes: function()
	{
		this.nodes.warning = BX.create('DIV', {
			props: {className: 'bx-filter-conditions-warning'},
			text: this.message.invalid,
			style: {display: 'none', color: 'red'}
		});
		this.nodes.container = BX.create('DIV', {props: {id: this.ids.container}});
		this.nodes.form = BX.create('FORM', {
			props: {id: this.ids.form, name: this.ids.form},
			children: [this.nodes.container],
			events: {
				change: BX.proxy(function(){
					this.saveData();
				}, this)
			}
		});

		this.params.oCont.appendChild(
			BX.create('DIV', {
				children: [
				this.nodes.warning,
				this.nodes.form
				]
			})
			);
	},

	saveData: function()
	{
		var systemData = {
			action: 'save',
			ids: this.ids,
			sessid: BX.bitrix_sessid()
		};

		BX.ajax({
			timeout: 60,
			method: 'POST',
			dataType: 'json',
			url: '?ajax_tree=1',
			data: BX.merge(this.getAllFormData(), systemData),
			onsuccess: BX.proxy(function(result){
				if (result === '')
				{
					this.nodes.warning.style.display = 'block';
				}
				else
				{
					this.nodes.warning.style.display = 'none';
					this.params.oInput.value = JSON.stringify(result);
				}
			}, this)
		});
	},

	getAllFormData: function()
	{
		var prepared = BX.ajax.prepareForm(this.nodes.form);

		for (var i in prepared.data)
		{
			if (prepared.data.hasOwnProperty(i) && i == '')
			{
				delete prepared.data[i];
			}
		}

		return !!prepared && prepared.data ? prepared.data : {};
	}
};
</script>