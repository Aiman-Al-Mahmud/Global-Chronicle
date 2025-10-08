@extends('admin.master')
@section('title', 'Add New User')
@section('style')
@stop
@section('content')
    <main class="main-content bgc-grey-100">
        <div id="mainContent">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="c-grey-900 mT-10 mB-30">Add New User</h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="bgc-white bd bdrs-3 p-20 mB-20">
                            <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name" class="label-control">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                                   value="{{ old('name') }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="label-control">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" 
                                                   value="{{ old('email') }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password" class="label-control">Password <span class="text-danger">*</span></label>
                                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_confirmation" class="label-control">Confirm Password <span class="text-danger">*</span></label>
                                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="role" class="label-control">Role <span class="text-danger">*</span></label>
                                            <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
                                                <option value="">Select Role</option>
                                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="editor" {{ old('role') == 'editor' ? 'selected' : '' }}>Editor</option>
                                                <option value="author" {{ old('role') == 'author' ? 'selected' : '' }}>Author</option>
                                                <option value="subscriber" {{ old('role') == 'subscriber' ? 'selected' : '' }}>Subscriber</option>
                                            </select>
                                            @error('role')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="is_active" class="label-control">Status</label>
                                            <select name="is_active" id="is_active" class="form-control">
                                                <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="description" class="label-control">Description</label>
                                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                                      rows="4">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="website" class="label-control">Website</label>
                                            <input type="url" name="website" id="website" class="form-control @error('website') is-invalid @enderror" 
                                                   value="{{ old('website') }}" placeholder="https://example.com">
                                            @error('website')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="profile_image" class="label-control">Profile Image</label>
                                            <input type="file" name="profile_image" id="profile_image" class="form-control @error('profile_image') is-invalid @enderror" 
                                                   accept="image/*">
                                            @error('profile_image')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="twitter_url" class="label-control">Twitter URL</label>
                                            <input type="url" name="twitter_url" id="twitter_url" class="form-control @error('twitter_url') is-invalid @enderror" 
                                                   value="{{ old('twitter_url') }}" placeholder="https://twitter.com/username">
                                            @error('twitter_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="facebook_url" class="label-control">Facebook URL</label>
                                            <input type="url" name="facebook_url" id="facebook_url" class="form-control @error('facebook_url') is-invalid @enderror" 
                                                   value="{{ old('facebook_url') }}" placeholder="https://facebook.com/username">
                                            @error('facebook_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="linkedin_url" class="label-control">LinkedIn URL</label>
                                            <input type="url" name="linkedin_url" id="linkedin_url" class="form-control @error('linkedin_url') is-invalid @enderror" 
                                                   value="{{ old('linkedin_url') }}" placeholder="https://linkedin.com/in/username">
                                            @error('linkedin_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group text-right">
                                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                                                <i class="fa fa-times"></i> Cancel
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-save"></i> Create User
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@stop

@section('script')
    <script>
        $(document).ready(function() {
            // Preview profile image
            $('#profile_image').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // You can add image preview here if needed
                        console.log('Image selected:', file.name);
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
@stop
