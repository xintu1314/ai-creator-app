import { Play } from 'lucide-react';

interface TutorialItem {
  id: string;
  title: string;
  video: string;
  description: string;
}

// 教程数据从后端获取（通过props传入）
interface TutorialProps {
  tutorials?: TutorialItem[];
}

export default function Tutorial({ tutorials = [] }: TutorialProps) {
  return (
    <div className="max-w-[1200px] mx-auto p-6">
      <h1 className="text-2xl font-semibold text-[#1A1A1A] mb-6">教程</h1>

      {/* Tutorials List */}
      {tutorials.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {tutorials.map((tutorial) => (
            <div
              key={tutorial.id}
              className="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-all duration-300 cursor-pointer"
            >
              {/* Video Preview */}
              <div className="relative aspect-video bg-[#F5F5F5]">
                {tutorial.video ? (
                  <div className="absolute inset-0 flex items-center justify-center">
                    <Play className="w-16 h-16 text-white bg-black/50 rounded-full p-4" />
                  </div>
                ) : (
                  <div className="absolute inset-0 flex items-center justify-center text-[#999999]">
                    <Play className="w-16 h-16" />
                  </div>
                )}
              </div>
              
              {/* Content */}
              <div className="p-4">
                <h3 className="text-lg font-medium text-[#1A1A1A] mb-2 line-clamp-2">
                  {tutorial.title}
                </h3>
                <p className="text-sm text-[#666666] line-clamp-3">
                  {tutorial.description}
                </p>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="text-center py-20 text-[#666666] bg-white rounded-lg border border-[#E5E5E5]">
          <p>暂无教程内容</p>
          <p className="text-sm mt-2">教程内容将通过后台管理上传</p>
        </div>
      )}
    </div>
  );
}
