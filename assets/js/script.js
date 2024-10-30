jQuery(function($){
    $( document ).on( "click",'.like_text',function() {
        var comment_id = jQuery(this).attr("data-cmt-id");
        var activity_id = jQuery(this).attr("data-act-id");
        var activity_snd_id = jQuery(this).attr("data-act-snd-id");
        var user_id = jQuery(this).attr("data-usr-id");
        var type = jQuery(this).attr("data-type");
        jQuery.ajax({
           type : "post",
           dataType: 'json',
           url : frontend_ajax.ajaxurl,
           data : {
               action: "buddypress_user_like", 
               comment_id : comment_id, 
               activity_id : activity_id ,
               activity_snd_id : activity_snd_id, 
               user_id: user_id,
               type : type
           },
           success: function(response) {
               if(response.like_count) {
                 var comment_res_id = response.comment_id;
                 jQuery(".like_text[data-cmt-id='" + comment_res_id +"']").next('.like_count').html(response.like_count);
               }
           }
        });
    });

    $( document ).on( "click",'.dislike_text',function() {
      var comment_id = jQuery(this).attr("data-cmt-id");
      var activity_id = jQuery(this).attr("data-act-id");
      var activity_snd_id = jQuery(this).attr("data-act-snd-id");
      var user_id = jQuery(this).attr("data-usr-id");
      var type = jQuery(this).attr("data-type");
      jQuery.ajax({
         type : "post",
         dataType: 'json',
         url : frontend_ajax.ajaxurl,
         data : {
             action: "buddypress_user_dislike", 
             comment_id : comment_id, 
             activity_id : activity_id ,
             activity_snd_id : activity_snd_id, 
             user_id: user_id,
             type : type
         },
         success: function(response) {
             if(response.dislike_count) {
               var comment_res_id = response.comment_id;
               jQuery(".dislike_text[data-cmt-id='" + comment_res_id +"']").next('.dislike_count').html(response.dislike_count);
             }
         }
      });
  });

});