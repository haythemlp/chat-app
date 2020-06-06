@extends('layouts.app')

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header">
                <div class="title">Laravel Chat Application with Firebase
                    <a href="#" id="logout" class="float-right"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout?</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                          style="display: none;">
                        @csrf
                    </form>
                </div>
                <div class="users">Loading users ...</div>
            </div>
            <div class="card-body">
                Loading content ...
            </div>
            <div class="card-footer">
                <form id="chat-form">
                    <div class="input-group">
                        <input type="text" name="content" class="form-control" placeholder="Type your message ..."
                               autocomplete="off">
                        <div class="input-group-btn">
                            <button class="btn btn-primary">Send</button>

                        </div>

                    </div>

                </form>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#videoChat">video
                </button>
            </div>
        </div>
    </div>


    <div class="modal fade" tabindex="-1" role="dialog" id="videoChat">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Welcome</h5>
                </div>
                <div class="modal-body row">
                    <div class="col-md-6">
                        <video id="yourVideo" class="w-100" autoplay muted playsinline></video>
                    </div>
                    <div class="col-md-6">
                        <video id="friendsVideo" class="w-100" autoplay playsinline></video>
                    </div>
                    <br/>
                </div>
                <div class="modal-footer">
                    <button onclick="showFriendsFace()" type="button" class="btn btn-primary btn-lg"><span
                                class="glyphicon glyphicon-facetime-video" aria-hidden="true"></span> Call
                    </button>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="myModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Welcome</h5>
                </div>
                <div class="modal-body">
                    <p>Before using this app, we need your nickname.</p>
                    <input type="text" class="form-control" name="user_name" placeholder="What's your nickname?">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="savename">Continue</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://www.gstatic.com/firebasejs/4.9.1/firebase.js"></script>
    <script>
        var old_users_val = $(".users").html();

        var scroll_bottom = function () {
            var card_height = 0;
            $('.card-body .chat-item').each(function () {
                card_height += $(this).outerHeight();
            });
            $(".card-body").scrollTop(card_height);
        }

        var escapeHtml = function (unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Initialize Firebase
        var config = {
            apiKey: '{{config('services.firebase.api_key')}}',
            authDomain: '{{config('services.firebase.auth_domain')}}',
            databaseURL: "{{config('services.firebase.database_url')}}",
            projectId: "{{config('services.firebase.project_id')}}",
            storageBucket: "{{config('services.firebase.storage_bucket')}}",
            messagingSenderId: "{{config('services.firebase.messaging_sender_id')}}"
        };
        firebase.initializeApp(config);

        // chats
        var users_name = [];

        function reindex_array_keys(array, start) {
            var temp = [];
            start = typeof start == 'undefined' ? 0 : start;
            start = typeof start != 'number' ? 0 : start;
            for (var i in array) {
                temp[start++] = array[i];
            }
            return temp;
        }

        firebase.database().ref('/chats').orderByChild("chat_id").equalTo('{{$id}}').on('value', function (snapshot) {
            var chat_element = "";
            if (snapshot.val() != null) {
                console.log(reindex_array_keys(snapshot.val()));
                reindex_array_keys(snapshot.val()).forEach(function (item, index) {
                    var chat_name = escapeHtml(item.name),
                        chat_content = escapeHtml(item.content);
                    users_name[index] = chat_name;

                    chat_element += '<div class="chat-item ' + item.type + '">';
                    chat_element += '<div class="chat-text">';
                    if (item.type == 'chat') {
                        chat_element += '<div class="chat-name">';
                        chat_element += chat_name;
                        chat_element += '</div>';
                    }
                    if (item.type == 'info') {
                        chat_element += chat_name + ' has joined the chat';
                    } else {
                        chat_element += chat_content;
                    }
                    chat_element += '</div>';
                    if (item.type == 'chat') {
                        chat_element += '<div class="chat-time">';
                        chat_element += item.created_at;
                        chat_element += '</div>';
                    }
                    chat_element += '</div>';

                    $(".card-body").html(chat_element);
                });

            } else {
                $(".card-body").html('<i>No chat</i>')
            }

            users_name = users_name.reverse();
            users_name = $.unique(users_name);

            var html_name = "";
            for (var i = 0; i < users_name.length; i++) {
                if (users_name[i] != undefined)
                    html_name += users_name[i] + ', ';
            }

            $(".card-header .users").html(html_name.substring(0, html_name.length - 2));
            old_users_val = $(".users").html();

            scroll_bottom();
        }, function (error) {
            alert('ERROR! Please, open your console.')
            console.log(error);
        });

        firebase.database().ref('/typing').on('value', function (snapshot) {
            var user = snapshot.val();
            if (user && user.name != user_name) {
                $(".users").html(user.name + ' is typing');
            } else {
                $(".users").html(old_users_val);
            }
        });

        // Get user name from localStorage
        var user_name = '{{auth()->user()->name}}';
        // If the user hasn't set their name
        var myModal;
        if (!user_name) {
            // Show modal
            myModal = $('#myModal').modal({
                backdrop: 'static'
            });

            // #savename action handler
            $("#savename").click(function () {
                var input_user_name = $("[name=user_name]").val();

                // Set user_name to localstorage
                localStorage.setItem("user_name", input_user_name);

                $.ajax({
                    url: '{{ route('chat.join') }}',
                    data: {
                        name: input_user_name
                    },
                    headers: {
                        'X-CSRF-TOKEN': $("meta[name=csrf-token]").attr('content')
                    },
                    type: 'post',
                    beforeSend: function () {
                        $(".card").addClass('card-progress');
                    },
                    success: function () {
                        $(".card").removeClass('card-progress');
                        var user_name_btn = (input_user_name.length > 15 ? input_user_name.substring(0, 12) + '...' : input_user_name);
                        $("#chat-form button").html('Send as ' + user_name_btn);
                    }
                });

                // Close modal
                myModal.modal('hide');
            });
        }

        // #logout action handler
        $("#logout").click(function () {
            var ask = confirm('Are you sure?');
            if (ask) {
                localStorage.removeItem("user_name");
                location.reload();
            }
            return false;
        });

        // Set the card height equal to the height of the window
        $(".card-body").css({
            height: $(window).outerHeight() - 200,
            overflowY: 'auto'
        });

        // Set button text
        if (user_name) {
            var user_name_btn = (user_name.length > 15 ? user_name.substring(0, 12) + '...' : user_name);
            $("#chat-form button").html('Send as ' + user_name_btn);
        }

        // #chat-form action handler
        $("#chat-form").submit(function () {
            var me = $(this),
                chat_content = me.find('[name=content]'),
                user_name = '{{auth()->user()->name}}';


            // if the value of chat_content is empty
            if (chat_content.val().trim().length <= 0) {
                // set focus to chat_content
                chat_content.focus();
            } else if (!user_name) {
                alert('We need your name!1!1!1!1');
            } else { // if the chat_content value is not empty
                $.ajax({
                    url: '{{ route('chat.store') }}',
                    data: {
                        content: chat_content.val().trim(),
                        name: user_name,
                        chat_id: "{{$id}}"
                    },
                    method: 'post',
                    headers: {
                        'X-CSRF-TOKEN': $("meta[name=csrf-token]").attr('content')
                    },
                    beforeSend: function () {
                        chat_content.attr('disabled', true);
                    },
                    complete: function () {
                        chat_content.attr('disabled', false);
                    },
                    success: function () {
                        chat_content.val('');
                        chat_content.focus();
                        scroll_bottom();
                    }
                });
            }

            return false;
        });

        var timer;
        $("#chat-form [name=content]").keyup(function () {
            var ref = firebase.database().ref('typing');
            ref.set({
                name: user_name
            });

            timer = setTimeout(function () {
                ref.remove();
            }, 2000);
        });
        /*


         */
        var database = firebase.database().ref("video");
        var yourVideo = document.getElementById("yourVideo");
        var friendsVideo = document.getElementById("friendsVideo");
        var yourId = Math.floor(Math.random() * 1000000000);

        var servers = {
            'iceServers': [{'urls': 'stun:stun.services.mozilla.com'},
                {'urls': 'stun:stun.l.google.com:19302'},
                {'urls': 'turn:numb.viagenie.ca', 'credential': '123654789lol', 'username': ' haythamov1993@gmail.com'}]
        };
        var pc = new RTCPeerConnection(servers);
        pc.onicecandidate = (event => event.candidate ? sendMessage(yourId, JSON.stringify({'ice': event.candidate})) : console.log("Sent All Ice"));
        pc.onaddstream = (event => friendsVideo.srcObject = event.stream);

        function sendMessage(senderId, data) {
            var msg = database.push({sender: senderId, message: data});
            msg.remove();
        }

        function readMessage(data) {
            console.log(data);
            var msg = JSON.parse(data.val().message);
            var sender = data.val().sender;
            if (sender != yourId) {
                if (msg.ice != undefined)
                    pc.addIceCandidate(new RTCIceCandidate(msg.ice));
                else if (msg.sdp.type == "offer")
                    pc.setRemoteDescription(new RTCSessionDescription(msg.sdp))
                        .then(() => pc.createAnswer())
                        .then(answer => pc.setLocalDescription(answer))
                        .then(() => sendMessage(yourId, JSON.stringify({'sdp': pc.localDescription})));
                else if (msg.sdp.type == "answer")
                    pc.setRemoteDescription(new RTCSessionDescription(msg.sdp));
            }
        };

        database.on('child_added', readMessage);

        function showMyFace() {
            navigator.mediaDevices.getUserMedia({audio: true, video: true})
                .then(stream => yourVideo.srcObject = stream)
                .then(stream => pc.addStream(stream));
        }

        function showFriendsFace() {
            pc.createOffer()
                .then(offer => pc.setLocalDescription(offer))
                .then(() => sendMessage(yourId, JSON.stringify({'sdp': pc.localDescription})));
        }
    </script>
@endsection
