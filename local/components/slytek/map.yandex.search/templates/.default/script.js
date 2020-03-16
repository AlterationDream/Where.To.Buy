var JCBXYandexSearch = function(map_id, obOut, jsMess)
{
	this.map_id = map_id;
	this.map = GLOBAL_arMapObjects['ShopsMap'];

	this.obOut = obOut;
	
	if (null == this.map)
		return false;

	this.arSearchResults = [];
	this.jsMess = jsMess;
};
window.search_in_process = false;
window.geosearch = {}
JCBXYandexSearch.prototype.__searchResultsLoad = function(res)
{
	
window.search_in_process = false;

	//$('.city-input').removeAttr('disabled').focus()

	if (null == this.obOut)
		return;

	this.obOut.innerHTML = '';
	this.clearSearchResults();

	var obList = null,
	len = res.geoObjects.getLength();
	var str = '';
	if (len > 0)
	{
		obList = document.createElement('UL');
		obList.className = 'shops-yandex-search-results';
		str = '';
		//str += this.jsMess.mess_search + ': <b>' + len + '</b> ' + this.jsMess.mess_found + '.';

		// i rly dont khow why this doesnt work in one loop ;-(
		for (var i = 0; i < len; i++)
		{
			this.arSearchResults.push(res.geoObjects.get(i));
		}

		for (i = 0; i < this.arSearchResults.length; i++)
		{
			//this.map.geoObjects.add(this.arSearchResults[i]);
			this.arSearchResults[i].getMapAlt = function(){
				return GLOBAL_arMapObjects['ShopsMap'];
			}
			var obListElement = obList.appendChild(BX.create('LI', {
				children: [
				BX.create('A', {
					attrs: {
						href: "javascript:void(0)"
					},
					events: {
						click: BX.proxy(this.__showSearchResult, this.arSearchResults[i])
					},
					text: this.arSearchResults[i].properties.get('metaDataProperty').GeocoderMetaData.text
				})
				]
			}));
		}

	}
	else
	{
		var obListElement = obList.appendChild(BX.create('LI', {
			children: [
			BX.create('A', {
				attrs: {
					href: "javascript:void(0)"
				},
				text: this.jsMess.mess_search_empty
			})
			]
		}));
	}

	this.obOut.innerHTML = str;

	

	if (null != obList)
		this.obOut.appendChild(obList);

	console.log(window.geosearch)

	if(window.geosearch['last']!=window.geosearch['current']){
		JCBXYandexSearch.prototype.searchByAddress(window.geosearch['current']);
	}
};

// called in the context of ymaps.Placement.
JCBXYandexSearch.prototype.__showSearchResult = function()
{
	this.getMapAlt().panTo(this.geometry.getCoordinates());
	$('#results_ShopsMap').hide();
	getPosition(this);
};

JCBXYandexSearch.prototype.searchByAddress = function(str)
{
	str = str.replace(/^[\s\r\n]+/g, '').replace(/[\s\r\n]+$/g, '');
	window.geosearch['current'] = str;
	if (str == '')
		return;
	if(!window.search_in_process){
		window.geosearch['last'] = str;
		window.search_in_process = true;
		//$('.city-input').attr('disabled', 'disabled')
		ymaps.geocode(str).then(
			BX.proxy(this.__searchResultsLoad, this),
			this.handleError
			);
	}
	
}

JCBXYandexSearch.prototype.handleError = function(error)
{
	alert(this.jsMess.mess_error + ': ' + error.message);
}

JCBXYandexSearch.prototype.clearSearchResults = function()
{
	
	this.arSearchResults = [];
}
