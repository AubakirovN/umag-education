Since you’re using Laravel 12 in the backend, you can implement the JSON-based approach while keeping queries efficient. Here’s how you can manage test questions, user answers, and evaluation in Laravel.

⸻

1. Migration for test_questions Table

Modify your Laravel migration to support JSON storage.
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestQuestionsTable extends Migration
{
    public function up()
    {
        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->json('answers'); // Stores {"A": "Option 1", "B": "Option 2"}
            $table->json('correct_answers'); // Stores {"A": "Option 1"}
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('course_block_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('test_questions');
    }
}

```

⸻

2. Migration for user_answers Table

We store user responses in JSON format.
```php
class CreateUserAnswersTable extends Migration
{
    public function up()
    {
        Schema::create('user_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('question_id');
            $table->json('selected_answers'); // Stores {"A": "test", "C": "test"}
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('test_questions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_answers');
    }
}

```

⸻

3. Laravel Models

TestQuestion Model
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'answers', 'correct_answers', 'course_id', 'course_block_id'];

    protected $casts = [
        'answers' => 'array',
        'correct_answers' => 'array',
    ];
}

```

⸻

UserAnswer Model
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'question_id', 'selected_answers', 'is_correct'];

    protected $casts = [
        'selected_answers' => 'array',
    ];
}
```


⸻

4. Storing User Answers

Frontend will send a POST request with selected answers:
```js
{
  "user_id": 123,
  "test_id": 10,
  "answers": [
    { "question_id": 1, "selected_answers": { "A": "Option 1" } },
    { "question_id": 2, "selected_answers": { "B": "Option 2", "C": "Option 3" } }
  ]
}
```
Controller Method to Save Answers
```php
use App\Models\UserAnswer;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function storeUserAnswers(Request $request)
    {
        $user_id = $request->input('user_id');
        $answers = $request->input('answers');

        foreach ($answers as $answer) {
            UserAnswer::create([
                'user_id' => $user_id,
                'question_id' => $answer['question_id'],
                'selected_answers' => $answer['selected_answers'],
            ]);
        }

        return response()->json(['message' => 'Answers saved successfully']);
    }
}

```

⸻

5. Evaluating Test Answers

We compare user_answers.selected_answers with test_questions.correct_answers.

Controller Method for Evaluation
```php
use App\Models\UserAnswer;
use App\Models\TestQuestion;

class TestController extends Controller
{
    public function evaluateTest($user_id)
    {
        $user_answers = UserAnswer::where('user_id', $user_id)->get();
        $total_questions = count($user_answers);
        $correct_count = 0;

        foreach ($user_answers as $answer) {
            $question = TestQuestion::find($answer->question_id);

            if ($question && json_encode($answer->selected_answers) === json_encode($question->correct_answers)) {
                $answer->update(['is_correct' => true]);
                $correct_count++;
            } else {
                $answer->update(['is_correct' => false]);
            }
        }

        $score = $total_questions > 0 ? round(($correct_count / $total_questions) * 100, 2) : 0;

        return response()->json([
            'user_id' => $user_id,
            'correct_answers' => $correct_count,
            'total_questions' => $total_questions,
            'score' => "{$score}%"
        ]);
    }
}

```

⸻

6. Fetching Test Results

Frontend can call:
```js
GET /api/evaluate-test/{user_id}

Example API Response

{
  "user_id": 123,
  "correct_answers": 8,
  "total_questions": 10,
  "score": "80%"
}
```


⸻

Summary
	•	Database with JSON-based answers
	•	User answers stored with JSON format
	•	Efficient evaluation logic in Laravel
	•	API response for frontend to display results

Would you like any additional features, such as storing test attempts or time tracking? 🚀