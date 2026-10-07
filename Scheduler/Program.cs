var builder = WebApplication.CreateBuilder(args);

// Add controller support
builder.Services.AddControllers();

// Add OpenAPI support
builder.Services.AddOpenApi();

var app = builder.Build();

// Configure the HTTP request pipeline
if (app.Environment.IsDevelopment())
{
    app.MapOpenApi();
}

// Map API controllers
app.MapControllers();

app.Run();