angular
    .module('productImporter.controllers')
    .controller('ProductController', function(
        $scope,
        $rootScope,
        ShopService,
        QueueService,
        TemplateService,
        Upload,
        toaster,
        FileService
    ) {
        $scope.shops = [];
        $scope.templates = [];
        $scope.fileUploaded = false;
        $scope.fileSelected = false;
        $scope.print_types = [
            { value: 'front_print', text: 'Front Print'},
            { value: 'back_print', text: 'Back Print'},
            { value: 'double_sided', text: 'Front & Back'}
        ];
        $scope.steps = [
            { id: 1, name: 'upload_file'},
            { id: 2, name: 'select_template'},
            { id: 3, name: 'advanced_settings'},
            { id: 4, name: 'google_logging'}
        ];
        $scope.current_step = $scope.steps[0];
        $scope.file = {};
        $scope.contents = [];
        $scope.productForm = {
            title: '',
            handle: '',
            type: 'Shirts',
            vendor: '',
            tags: '',
            template: 'single_product',
            log_to_google: true,
            print_type: 'front_print',
            front_print_url: '',
            back_print_url: '',
            uploaded_file: '',
            file_name: ''
        }
        ShopService.getShops().then(function(response) {
            $scope.shops = response.data.shops;
        })
        TemplateService.getTemplates().then(function(response) {
            $scope.templates = response.data.templates;
        })
        $scope.upload = function(file) {
            $scope.file = file;
            $scope.fileSelected = true;
            $scope.productForm.uploaded_file = file.name;
            Upload.upload({
                url: '/files',
                data: {
                    file: file
                }
            }).then(function (resp) {
                toaster.success("File finished uploading");
                FileService.getFile(resp.data.uploaded_file_name).then(function(response) {
                    $scope.contents = response.data;
                    console.log($scope.contents);
                })
                console.log('Success ' + resp.config.data.file.name + 'uploaded. Response: ' + resp.data);
            }, function (resp) {
                console.log('Error status: ' + resp.status);
            }, function (evt) {
                var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
                console.log('progress: ' + progressPercentage + '% ' + evt.config.data.file.name);
            });
        }
    })
