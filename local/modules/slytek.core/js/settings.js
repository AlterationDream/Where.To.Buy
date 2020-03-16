var SlytekSettings = function(arProps, arTypes, container){
    if(!container)container=$('body')
        this.arTypes=arTypes;
    this.bind_events(container);
    this.buildTree(arProps, container.find('.button-row').find('input[type="button"]'));
    var __this = this;
    var max = 0;
    container.find('.property-row').each(function(){
        var level = parseInt($(this).attr('data-level'));
        if(level>max)max=level;
    })
    for(var i = max; i>0; i--){
        container.find('.property-row[data-level="'+i+'"]').each(function(){
            __this.disableChecks($(this), true);
        })
    }
}
SlytekSettings.prototype.addPropRow=function(arProperty, btn){
    var level=1, newp=false, code='', newp=false;
    if(!btn){
        btn=$('.button-row').find('input[type="button"]');
    }
    level=btn.parents('.children-container').length+1;
    if(level>1){
        code=btn.closest('.children-container').prev('.property-row').attr('data-code');
    }
    if(!arProperty){
        newp=true;
        arProperty={NAME: '',CODE: '', TYPE: 'string'};
        if(level>1)
         arProperty['CODE']='n'+btn.closest('.children-container').find('.new-property[data-level="'+level+'"]').length;
     else arProperty['CODE']='n'+$('.new-property[data-level="'+level+'"]').length;
 }
 if(code)code=code+'[CHILDRENS]['+ arProperty['CODE']+']';
 else code='PROPERTIES['+arProperty['CODE']+']';

 var typesHtml='';
 for(var cd in this.arTypes){
    typesHtml+='<option '+(arProperty['TYPE']==cd?'selected':'')+' value="'+cd+'">'+this.arTypes[cd]+'</option>';
}
var property=$('<tr class="property-row '+(newp?'new-property':'')+'" data-level="'+level+'" data-code="'+code+'">'+
    '<td><table><tr><td>Название </td><td><input type="text" name="'+code+'[NAME]" value="'+arProperty['NAME']+'"></td></tr></table></td>'+
    '<td><table><tr><td>Код </td><td><input class="code" type="text" name="'+code+'[CODE]" value="'+arProperty['CODE']+'"></td></tr></table></td>' +
    '<td><table><tr><td>Тип </td><td><select name="'+code+'[TYPE]" class="select-type">' +
    typesHtml+
    '</select></td></tr></table></td>'+
    '<td><table><tr><td><input class="savedb-check" type="checkbox" name="'+code+'[SAVEDB]" '+(arProperty['SAVEDB']?'checked':'')+' value="1"></td>' +
    '<td><label>Хранить в БД (иначе в файле) </label></td>'+
    '<td><input class="serialize-check" type="checkbox" name="'+code+'[SERIALIZE]" '+(arProperty['SERIALIZE']?'checked':'')+' value="1"></td>' +
    '<td><label>Сериализовать свойство </label></td><td>'+
    '<input type="button" class="upProp" value="&uarr;"><input type="button" value="&darr;" class="downProp">'+
    '<input type="button" class="removeProp" value="x"></td></tr></table></td></tr>'+
    '</tr>'
    );
var start=btn.closest('.button-row'); 
property.insertBefore(start);
this.bind_events(property);
this.disableChecks(property);
if(arProperty['TYPE']=='complex' || arProperty['TYPE']=='complex_page'){
    var row = this.complex(property.find('select')[0], arProperty);
    return row.find('.button-row input');
}

return property;
}

SlytekSettings.prototype.buildTree = function(arProperties, btn){
    for(code in arProperties){
        var arProperty=arProperties[code];
        var current=this.addPropRow(arProperty, btn);
        if(this.size(arProperty['CHILDRENS'])>0){
            this.buildTree(arProperty['CHILDRENS'], current);
        }

    }
}
SlytekSettings.prototype.removeProp = function(btn){
    var r=$(btn).closest('.property-row');
    r.next('.children-container').remove();
    r.remove();
    BX.proxy(this._recalc_pos, this);
}
SlytekSettings.prototype.upProp = function(obj){
    var r=$(obj).closest('.property-row'),
    rm=false;
    if(r.next().hasClass('children-container')){
        rm=r.next();
    }
    if(r.prev().length>0){
        if(r.prev().hasClass('children-container')){
            r.prev().prev().before(r);
        }else{
         r.prev().before(r);
     }
     if(rm)r.after(rm);
 }
 BX.proxy(this._recalc_pos, this);
}
SlytekSettings.prototype.downProp = function(obj){
    var r=$(obj).closest('.property-row'), last=r
    rm=false;
    if(r.next().hasClass('children-container')){
        rm=r.next();
        last=rm;
    }
    if(last.next().length>0 && !last.next().hasClass('button-row')){
        if(last.next().next().hasClass('children-container')){
            last.next().next().after(r)
        }else{
            last.next().after(r)
        }
        if(rm)r.after(rm);
    }
    BX.proxy(this._recalc_pos, this);
}
SlytekSettings.prototype.disableChecks = function(row, checked){
    if(row.next().hasClass('children-container')){
        var input = row.find('input[type="checkbox"]:checked');
        if(checked)input = row.find('input[type="checkbox"][checked], input[type="checkbox"]:checked');
        if(input.length>0){
            row.next().find('.savedb-check').attr('disabled', 'disabled')
            row.next().find('.serialize-check').attr('disabled', 'disabled')
        }else{
         row.next().find('.savedb-check').prop('disabled', false)
         row.next().find('.serialize-check').prop('disabled', false)
     }
 }
}
SlytekSettings.prototype.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
}
SlytekSettings.prototype.complex = function(select, arProperty, remove) {
    var property_row=$(select).closest('.property-row');

    if(remove){
        property_row.next('.children-container').remove();
    }else if( property_row.next('.children-container').length==0){
        var row=$('<tr class="children-container" data-level="'+property_row.attr('data-level')+'">'+
            '<td colspan="5">'+
            '<div class="adm-detail-content-item-block-view-tab">'+
            '<table class="edit-table"><tr class="heading">'+
            '<td colspan="4">Дочерние свойства для '+(arProperty?'['+arProperty['CODE']+'] '+arProperty['NAME']:'')+'</td>'+
            '</tr>'+
            '<tr class="button-row"><td><input type="button" title="+" value="Добавить свойство"></td></tr>'
            );
        property_row.after(row);
        this.bind_events(row);
        return row;
    }
}
SlytekSettings.prototype.bind_events = function(container) {
    var _this=this;
    
    container.find('.button-row input[type="button"]').on('click', function(e){
        e.preventDefault();
        _this.addPropRow(false, $(this));
        return false;
    })
    container.find('select.select-type').on('change', function(e){
        var val=this.value;
        if(val=='complex' || val=='complex_page'){
            _this.complex(this);
        }else{
            _this.complex(this, false, true);
        }
    })
    container.find('.upProp').on('click', function(e){
        e.preventDefault();
        _this.upProp(this);
        return false;
    })
    container.find('.downProp').on('click', function(e){
        e.preventDefault();
        _this.downProp(this);
        return false;
    })
    container.find('.removeProp').on('click', function(e){
        e.preventDefault();
        _this.removeProp(this);
        return false;
    })
    container.find('.serialize-check, .savedb-check').on('change', function(e){
        e.preventDefault();
        _this.disableChecks(container);
        return false;
    })
}
