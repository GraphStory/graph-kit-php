$( document ).ready(function() {
    getTags();
    searchProducts();
    menu();
    loadMoArProducts();
});

function menu(){
    var pgurl = window.location.href;
    $("#socialli").addClass("active");
    $("#graphstorynav li a").each(function(){

        if(pgurl.indexOf($(this).attr("href")) > -1){
            $("#socialli").removeClass("active");
            $(this).parent().addClass("active");
        }

    });
}

$('#updateUser').on("click", function() {
    updateUser();
    return false;
});

$('#socialfriendsearch').on("click", function() {
    searchByUsername($('#username').val());
    return false;
});

$('#userstoadd').on('click', 'a.addfriend', function() {
    addfriend($(this).attr('id'));
    $(this).closest("tr").remove();
    return false;
});

$('#following').on('click', 'a.removefriend', function() {
    removefriend($(this).attr('id'));
    return false;
});

$('#morecontent').on('click', 'a.next', function() {
    getcontent(parseInt($("#contentcount").val())+1);
    return false;
});

$('#contentAddEdit').on('click', 'a#addcontent', function() {
    if ( $("#contentform").is(":visible") ) {
        $("#addcontent").text('Add Content');
        $("#contentform")[0].reset();
        $("#contentform").hide();
    } else {
        $("#contentform").show();
        $("#addcontent").text("Cancel");
        $("#btnSaveContent").text('Add Content');
    }

    return false;
});


$('#btnSaveContent').click(function() {
    if ($('#contentId').val() === undefined){
        addContent();
        return false;
    } else {
        updateContent();
        return false;
    }
});

$('body').on('click','a.deleteContent', function() {
    var id = $(this).attr('id');
    id = id.replace("delete_","");

    $.ajax({
        type: 'GET',
        url: '/posts/delete/' + id,
        success: function(data, textStatus, jqXHR){
            id = "tr#_"+id;
            $(id).remove();
            alert('Content deleted');
        }
    });

    return false;
});

$('body').on('click','a.editcontent', function() {
    var id = $(this).attr('id');
    id = id.replace("edit_","");

    if ( $("#contentform").is(":visible") ) {
        $("#addcontent").text('Add Content');
        $("#contentform")[0].reset();
        $("#contentform").hide();
    }else{
        var p = $("#contentAddEdit").position();
        $(window).scrollTop(p.top);
        $("#contentform").show();
        $("#addcontent").text("Cancel");
        $("#contentId").val(id);
        $("#title").val($("#url_"+id).text());
        $("#url").val($("#url_"+id).attr('href'));
        $("#tagstr").val($("#tags_"+id).text());
        $("#btnSaveContent").text('Edit Content');

    }
    return false;
});

$('ul').on('click','a.productNodeId', function() {
    var productNodeId= $(this).attr("id");
    var id = "#pdescr_"+productNodeId;

    if ( $(id).is(":visible") )
        {
            $(".list-group-item").removeClass("active");
            $(".productdescr").hide();
            $(".productNodeId").text("See Product Description...");
        }
        else
            {
                $(".productNodeId").text("See Product Description...");
                $(".list-group-item").removeClass("active");
                $(".productdescr").hide();
                $(id).parent().addClass("active");
                $(id).show();
                $(this).text("Close");
                createUserProductViewRel(productNodeId);
            }

            return false;
});

$('td').on('click','a.productNodeId', function() {
    var productNodeId= $(this).attr("id");
    var id = "#pdescr_"+productNodeId;

    if ( $(id).is(":visible") )
        {
            $(".productdescr").hide();
        }
        else
            {
                $(".productdescr").hide();
                $(id).show();
            }

            return false;
});

function updateUser() {
    $.ajax({
        type: 'PUT',
        contentType: 'application/json',
        url: '/user/edit',
        dataType: "json",
        data: userformToJSON(),
        success: function(data, textStatus, jqXHR){
            alert('User updated');
        },
        error: function(jqXHR, textStatus, errorThrown){
            alert('User update error: ' + textStatus);
        }
    });
}

function searchByUsername(u) {
    $.ajax({
        type: 'GET',
        url: '/searchbyusername/' + u,
        dataType: "json",
        success: renderSearchByUsername
    });
}

function addfriend(u) {
    $.ajax({
        type: 'GET',
        url: '/follow/'+u,
        dataType: "json",
        success: renderFollowers
    });
}

function removefriend(u) {
    $.ajax({
        type: 'GET',
        url: '/unfollow/'+u,
        dataType: "json",
        success: renderFollowers
    });
}

function getcontent(skip){
    $.ajax({
        type: 'GET',
        url: '/postsfeed/'+skip,
        dataType: "json",
        success: showContentStream
    });
}

function addContent() {
    $.ajax({
        type: 'POST',
        contentType: 'application/json',
        url: '/posts/add',
        dataType: "json",
        data: contentformToJSON(),
        success: function(data, textStatus, jqXHR){

            $('#content').prepend('<tr><td><a href="'+data.url+'" target="_blank">'+data.title+'</a>   <br/>tags: '+data.tagstr+' ::   Posted by '+data.userNameForPost+' at <a href="/viewpost/'+data.contentId+'">'+data.timestamp+'</a><br/></td></tr>');

            $('#title').val("");
            $('#url').val("");

            $("#contentform").hide();
            $("#addcontent").text('Add Content');
        },
        error: function(jqXHR, textStatus, errorThrown){
            alert('Add content error: ' + errorThrown);
        }
    });
}

function updateContent() {

    var contentId =$("#contentId").val();
    var json = ConvertFormToJSON($("#contentform"));
    json=JSON.stringify(json);
    console.log(json);
    $.ajax({
        type: 'PUT',
        contentType: 'application/json',
        url: '/posts/edit',
        dataType: "json",
        data: json,
        success: function(data, textStatus, jqXHR){

            $("#url_"+contentId).text(data.title);
            $("#url_"+contentId).attr('href',data.url);
            $("#tags_"+contentId).text(data.tagstr);
            $("#addcontent").text('Add Content');
            $("#contentform")[0].reset();
            $("#contentform").hide();
            alert('Content updated');
        },
        error: function(jqXHR, textStatus, errorThrown){
            alert('Content update error: ' + textStatus);
        }
    });
}

function createUserProductViewRel(productNodeId){
    $.ajax({
        type: 'GET',
        url: '/consumption/add/'+productNodeId,
        dataType: "json",
        success: function(data, textStatus, jqXHR){
            $("#userProductTrail").empty();

            $.each(data.productTrail, function(index, item) {
                $('#userProductTrail').append('<p><b>'+item.product.title+'</b><br> last viewed on: '+item.dateAsStr+'</p>');
            });

        },
        error: function(jqXHR, textStatus, errorThrown){
            alert('createUserProductViewRel add error: ' + textStatus);
        }
    });
}

function renderSearchByUsername(data) {
    var list = data == null ? [] : (data.users instanceof Array ? data.users : [data.users]);

    $('#userstoadd tr').remove();

    if (list.length <= 0 ) {
        $('#userstoadd').append('<tr><td>No Users Found<td></tr>');
    }

    $.each(list, function(index, users) {
        $('#userstoadd').append('<tr><td>' + users.username + '<td><td><a href="#" id="' + users.username + '" class="addfriend">Add as Friend</a></td></tr>');
    });
}

function renderFollowers(data) {
    var list = data == null ? [] : (data.following instanceof Array ? data.following : [data.following]);

    $('#following tr').remove();

    if (list.length <= 0 ) {
        $('#following').append('<tr><td>No Friends<td></tr>');
    }
    $.each(list, function(index, following) {
        $('#following').append('<tr><td>' + following.username + '<td><td><a href="#" id="' + following.username + '" class="removefriend">Remove</a></td></tr>');
    });
}

function showContentStream(data) {
    var list = data == null ? [] : (data.content instanceof Array ? data.content : [data.content]);
    $tr = $('#morecontent');
    $tr.hide();
    $.each(list, function (index, content) {
        if (index < 3) {
            var id = content.contentId;
            ti = (content.owner ? '<a class="editcontent" id="edit_' + id + '">Edit</a> <a class="deletecontent" id="delete_' + id + '">Delete</a>' : '');

            $('#content').append('<tr id="_' + id + '"><td><a href="' + content.url + '" id="url_' + id + '" target="_blank">' + content.title + '</a> ' + ti + '<br/>tags:<span id="tags_' + id + '">' + content.tagstr + '</span> ::   Posted by ' + content.userNameForPost + ' at <a href="/viewpost/' + content.contentId + '">' + content.timestampAsStr + '</a> </td></tr>');
        }
    });

    if (list.length>=4) {
        contentcount = parseInt($("#contentcount").val()) + 3;
        $("#contentcount").val(contentcount);
        $('#content').append($tr);
        $tr.show();
    } else {
        $('#content').append('<tr><td>No more content :(</td></tr>');
    }
}

//Helper function to serialize all the form fields into a JSON string
function userformToJSON() {
    return JSON.stringify({"firstname": $('#firstname').val(), "lastname": $('#lastname').val() });
}

//Helper function to serialize all the form fields into a JSON string
function contentformToJSON() {

    if($("#tagstr").length == 0) {
        return JSON.stringify({"title": $('#title').val(), "url": $('#url').val() });
    }else{
        return JSON.stringify({"title": $('#title').val(), "url": $('#url').val(), "tagstr": $('#tagstr').val() });
    }
}

function ConvertFormToJSON(form){
    var array = jQuery(form).serializeArray();
    var json = {};

    jQuery.each(array, function() {
        json[this.name] = this.value || '';
    });

    return json;
}

function getTags(){
    // get tags via autocomplete - THIS IS FOR THE SOCIAL GRAPH / CONTENT SECTION
    $(document).on('keyup.autocomplete','input[name="tagstr"]', function(event){
        if (event.keyCode === $.ui.keyCode.TAB && $( this ).data( "autocomplete" ).menu.active )
            {
                event.preventDefault();
            }

            if(event.keyCode === $.ui.keyCode.COMMA){
                this.value = this.value+" ";
            }

            $(this).autocomplete({

                source: function( request, response ) {
                    $.getJSON(  "/tag/" + extractLast( request.term.toLowerCase() ) + ".json", response );
                },
                search: function() {
                    // custom minLength
                    var term = extractLast( this.value );
                    if ( term.length < 2 ) {
                        return false;
                    }
                },
                focus: function() {
                    // prevent value inserted on focus
                    return false;
                },
                select: function( event, ui ) {

                    var terms = split( this.value );
                    // remove the current input
                    terms.pop();

                    // add the selected item
                    terms.push( ui.item.value );
                    // add placeholder to get the comma-and-space at the end
                    terms.push( "" );
                    this.value = terms.join( "," );
                    this.value = this.value + " ";
                    return false;
                }
            });
    });
}

function searchProducts(){
    // get products via autocomplete - THIS IS FOR THE LOCATION GRAPH SECTION
    $(document).on('keyup.autocomplete','input[name="product"]', function(event){
        if (event.keyCode === $.ui.keyCode.TAB && $( this ).data( "autocomplete" ).menu.active )
            {
                event.preventDefault();
            }

            if(event.keyCode === $.ui.keyCode.COMMA){
                this.value = this.value+" ";
            }

            $(this).autocomplete({

                source: function( request, response ) {
                    $.getJSON(  "/productsearch/" + extractLast( request.term.toLowerCase() ) + ".json", response );
                },
                search: function() {
                    // custom minLength
                },
                focus: function() {
                    // prevent value inserted on focus
                    return false;
                },
                select: function( event, ui ) {
                    this.value = ui.item.value;
                    $("#productNodeId").val(ui.item.id);
                    return false;
                }
            });
    });
}

function split( val ) {
    return val.split( /[,]+\s\s*/ );
}

function extractLast( term ) {
    return split( term ).pop();
}

function loadMoArProducts(){
    $('ul#productlist').jscroll({
        loadingHtml: '<div align="center" style="margin: 20px 0 20px 0"><li id="feed_load"><img src="/resources/img/loader.gif" alt="ajax loading indicator" class="ajax-loader"> &nbsp; Loading more products...</li></div>',
        padding: 10,
        nextSelector: 'a.jscroll-next:last',
        callback: postFeedLoad
    });
}

function postFeedLoad(){
    // you could do somthing here if necessary after a new page loads via jscroll.
    // I added this because that does happen. you're welcome.
}
