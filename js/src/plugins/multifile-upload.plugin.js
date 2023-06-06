import $ from 'jquery';
import atkPlugin from './atk.plugin';
import multiuploadService from "../services/multiupload.service";

export default class multifileUpload extends atkPlugin {

  main() {
    this.textInput = this.$el.find('input[type="text"]');
    this.hiddenInput = this.$el.find('input[type="hidden"]');

    this.fileInput = this.$el.find('input[type="file"]');
    this.action = this.$el.find('#' + this.settings.action);
    this.actionContent = this.action.html();
    this.action.css({'right':'0.7em', 'position' : 'absolute', 'top' : '.78571429em', 'margin' : '-.78571429em', 'width' : 'auto', 'cursor' : 'pointer', });
 
    this.uploadicon = this.$el.find('i[class="dropdown icon"]');
    this.uploadicon.hide();
 	   
    this.searchitem = this.$el.find('input[class="search"]');
    this.searchitem.attr('disabled', 'disabled');
	
    this.bar = this.$el.find('.progress');
    this.setEventHandler();
    this.setInitialState();

  }

  /**
   * Setup field initial state.
   */
  setInitialState() {
    // Set progress bar.
    this.bar.progress({
      text : {
        percent: '{percent}%',
        active: '{percent}%',
      }
    }).hide();

    this.$el.data().fileId = this.settings.file.id;
    this.hiddenInput.val(this.settings.file.id);
    this.textInput.val(this.settings.file.name);
    this.textInput.data('isTouch', false);
    if (this.settings.file.id) {
      this.setState('added');
    }
    
    if (this.action.hasClass('disabled')) {
      this.$el.find('i[class="delete icon"]').hide();
    }
  }

  /**
   * Update input value.
   *
   * @param fileId
   * @param fileName
   */
  updateField(fileId, fileName) {
    var arr =[]
    var fileIdList
    
    if (this.hiddenInput.val()) {
    	arr = this.hiddenInput.val().split(',')
    	arr.push(fileId)
    	fileIdList = arr.join(',')
    } else {
    	fileIdList = fileId
    }
    this.$el.data().fileId = fileIdList
    const that = this
    this.hiddenInput.val(fileIdList) // 
  	this.searchitem.before('<a class="ui label transition visible" data-value="' + fileId +'" style="display: inline-block !important;"><div class="filetitle" style="display: inline-block">' + fileName + '</div><i class="delete icon"></i></a>')
  		
    if (fileName === '' || typeof fileName === 'undefined' || fileName === null) {
      this.textInput.val(fileIdlist);
    } else {
      this.textInput.val(fileName);
    }
  }

  /**
   * Add event handler to input element.
   */
  setEventHandler() {
    const that = this;

    this.textInput.on('click', (e) => {
      if (!e.target.value) {
        that.fileInput.click();
      }
    });
    
    

    // add event handler to action button.
    this.action.on('click', (e) => {
      if (!that.textInput.val()) {
        that.fileInput.click();
      } else {
        // When upload is complete a js action can be send to set an id
        // to the uploaded file via the jQuery data property.
        // Check if that id exist and send it with
        // delete callback, If not, default to file name.
        let id = that.$el.data().fileId;
        if (id === '' || typeof id === 'undefined' || id === null) {
          id = that.textInput.val();
        }
//        that.doFileDelete(id);
        that.fileInput.click();
      }
    });

 this.uploadicon.on('click', (e) => {
      if (!that.textInput.val()) {
        that.fileInput.click();
      } else {
        // When upload is complete a js action can be send to set an id
        // to the uploaded file via the jQuery data property.
        // Check if that id exist and send it with
        // delete callback, If not, default to file name.
        let id = that.$el.data().fileId;
        if (id === '' || typeof id === 'undefined' || id === null) {
          id = that.textInput.val();
        }
        that.fileInput.click();
      }
    });
    
	// Add click events for items.

    this.$el.find('.filetitle').on('click', '', (e) => {
	  let id = $(e.target).parent().data('value');
      that.doFileDownload(id);
    });
    
  	this.$el.find('.delete.icon').on('click', '', (e) => {
  	  let id = $(e.target).parent().data('value');
  	  that.doFileDelete(id);
      let arr = this.hiddenInput.val().split(',')
      this.hiddenInput.val(arr.filter(item => item !== id).join(','))
    });

    // add event handler to file input.
    this.fileInput.on('change', (e) => {
      if (e.target.files.length > 0) {
        that.textInput.val( e.target.files[0].name);
        //that.doFileUpload(e.target.files[0]);
        that.domultiFileUpload(e.target.files);
      }
    });
  }

  /**
   * Set the action button html content.
   * Set the input text content.
   */
  setState(mode) {
    const that = this;

    switch (mode) {
      case 'added':
        //this.action.html(this.getEraseContent);
        setTimeout(() => {
          that.bar.progress('reset');
          that.bar.hide('fade');
        }, 1000);
        break;

    }
  }

  /**
   * Do the actual file uploading process.
   *
   * @param file the FileList object.
   */
  domultiFileUpload(file) {

    const that = this;
    // if submit button id is set, then disable submit
    // during upload.
    if (this.settings.submit) {
      $('#'+this.settings.submit).addClass('disabled');
    }

    // setup task on upload completion.
    let completeCb =  (response, content) => {
      if (response.success) {
        that.bar.progress('set label', that.settings.completeLabel);
         that.setState('added');
      }

      if (that.settings.submit) {
        $('#'+that.settings.submit).removeClass('disabled');
      }
    };

    // setup progress bar update via xhr.
    let xhrCb = () => {
      let xhr = new window.XMLHttpRequest();
      xhr.upload.addEventListener("progress",  (evt) => {
        if (evt.lengthComputable) {
          let percentComplete = evt.loaded / evt.total;
          that.bar.progress('set percent', parseInt(percentComplete * 100));
        }
      }, false);
      return xhr;
    };

    that.bar.show();
    multiuploadService.multiuploadFiles(
      file,
      this.$el,
      {f_upload_action: 'upload'},
      this.settings.uri,
      completeCb,
      xhrCb
    );
  }

  /**
   * Callback server for file delete.
   *
   * @param fileName
   */
  doFileDelete(fileName) {

    const that = this;

    this.$el.api({
      on: 'now',
      url: this.settings.uri,
      data: {f_upload_action: 'delete', 'f_name': fileName},
      method: 'POST',
      obj: this.$el,
      onComplete: (response, content) => {
        if (response.success) {
        //  that.setState('upload');
        }
      }
    });
  }
  
  /**
   * Callback server for file download.
   *
   * @param fileName
   */
  doFileDownload(fileName) {

    const that = this;

    this.$el.api({
      on: 'now',
      url: this.settings.uri,
      data: {f_upload_action: 'download', 'f_name': fileName},
      method: 'POST',
      obj: this.$el,
      onComplete: (response, content) => {
        if (response.success) {
        //  that.setState('upload');
        }
      }
    });
  }

  /**
   * Return the html content for erase action button.
   *
   * @returns {string}
   */
  getEraseContent() {
    return `<i class="red remove icon" style=""></i>`;
  }
}


multifileUpload.DEFAULTS = {
  uri: null,
  file: {id: null, name: null},
  uri_options: {},
  action: null,
  completeLabel: '100%',
  submit: null
};
